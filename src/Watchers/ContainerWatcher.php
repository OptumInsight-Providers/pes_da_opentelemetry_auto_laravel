<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Instrumentation\Laravel\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SemConv\TraceAttributes;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ContainerWatcher extends Watcher
{
    /**
     * @var array<string, SpanInterface>
     */
    protected array $spans = [];

    public function __construct(
        private CachedInstrumentation $instrumentation,
    ) {
    }

    /**
     * @psalm-suppress UndefinedInterfaceMethod
     * @suppress PhanTypeArraySuspicious
     */
    public function register(Application $app): void
    {
	$app->resolving(function (mixed $object, Application $app) {
		$namespace = is_string($object) ? $object : get_class($object);
		//var_dump($namespace);
		/*
        	$span = $this->instrumentation->tracer()->spanBuilder($request->request->method())
            		->setSpanKind(SpanKind::KIND_CLIENT)
            		->setAttributes([
                		TraceAttributes::HTTP_REQUEST_METHOD => $request->request->method(),
                		TraceAttributes::URL_FULL => $processedUrl,
                		TraceAttributes::URL_PATH => $parsedUrl['path'] ?? '',
                		TraceAttributes::URL_SCHEME => $parsedUrl['scheme'] ?? '',
                		TraceAttributes::SERVER_ADDRESS => $parsedUrl['host'] ?? '',
                		TraceAttributes::SERVER_PORT => $parsedUrl['port'] ?? '',
            		])->startSpan();
		$span->end();
		 */
		if(str_starts_with($namespace, "App")){
			//var_dump($object);
			$span = $this->instrumentation->tracer()->spanBuilder($namespace)
                        ->setSpanKind(SpanKind::KIND_SERVER)
                        ->startSpan();
                        $span->end();
		}
	});
    }

}
