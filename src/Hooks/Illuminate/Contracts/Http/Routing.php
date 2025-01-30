<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Laravel\Hooks\Illuminate\Contracts\Http;

use  Illuminate\Routing\ControllerDispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Contrib\Instrumentation\Laravel\Hooks\LaravelHook;
use OpenTelemetry\Contrib\Instrumentation\Laravel\Hooks\LaravelHookTrait;
use OpenTelemetry\Contrib\Instrumentation\Laravel\Hooks\PostHookTrait;
use OpenTelemetry\Contrib\Instrumentation\Laravel\Propagators\HeadersPropagator;
use OpenTelemetry\Contrib\Instrumentation\Laravel\Propagators\ResponsePropagationSetter;
use function OpenTelemetry\Instrumentation\hook;
use OpenTelemetry\SemConv\TraceAttributes;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Routing implements LaravelHook
{
    use LaravelHookTrait;
    use PostHookTrait;

    public function instrument(): void
    {
        $this->hookHandle();
    }

    protected function hookHandle(): bool
    {
        return hook(
            ControllerDispatcher::class,
            'dispatch',//'resolveParameters',//'dispatch',
            pre: function (ControllerDispatcher $dispatcher, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
                /** @psalm-suppress ArgumentTypeCoercion */
                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder(($params[1]::class ?? "UnKnownController")."->".($params[2] ?? 'UnknownMethod'))
                    ->setSpanKind(SpanKind::KIND_SERVER)
                    ->setAttribute(TraceAttributes::CODE_FUNCTION, $params[2] ?? 'unknown')
                    ->setAttribute(TraceAttributes::CODE_NAMESPACE, $params[1]::class ?? 'unknown');
                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));
            },
            post: function (ControllerDispatcher $dispatcher, array $params, mixed $return, ?Throwable $exception) {
                $scope = Context::storage()->scope();
                if (!$scope) {
                    return;
                }
                $span = Span::fromContext($scope->context());

                //$span->setAttribute("code.parameter", json_encode($return));

                $this->endSpan($exception);
		//$span->end();
            }
        );
    }
}
