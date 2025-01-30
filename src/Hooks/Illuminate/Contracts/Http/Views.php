<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Laravel\Hooks\Illuminate\Contracts\Http;

use Illuminate\View\View;
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

class Views implements LaravelHook
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
            View::class,
            '__construct',
	    pre: function (View $view, array $params, string $class, string $function, ?string $filename, ?int $lineno) {
		    /** @psalm-suppress ArgumentTypeCoercion */
		    //var_dump($params[3], $params[4]);
                $builder = $this->instrumentation
                    ->tracer()
                    ->spanBuilder("View: ".($params[2] ?? "Unknown"))
                    ->setSpanKind(SpanKind::KIND_SERVER)
                    ->setAttribute("code.parameter", json_encode($params[4]))
                    ->setAttribute(TraceAttributes::CODE_NAMESPACE, $params[3] ?? 'unknown');
	    	$parent = Context::getCurrent();
		//$scope = $parent->activate();
		$span = $builder->startSpan();
		Context::storage()->attach($span->storeInContext($parent));
		//$scope->detach();
		//$span->end();
		$this->endSpan();
            },
        );
    }
}
