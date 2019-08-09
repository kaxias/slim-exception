<?php

/*
 * slim-exception (https://github.com/juliangut/slim-exception).
 * Slim exception handling.
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-exception
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

declare(strict_types=1);

namespace Jgut\Slim\Exception\Renderer;

use Slim\Interfaces\ErrorRendererInterface;

/**
 * JSON exception renderer.
 */
class JsonRenderer implements ErrorRendererInterface
{
    /**
     * JSON encoding options.
     * Preserve float values and encode &, ', ", < and > characters in the resulting JSON.
     */
    const JSON_ENCODE_OPTIONS = \JSON_UNESCAPED_UNICODE
        | \JSON_UNESCAPED_SLASHES
        | \JSON_PRESERVE_ZERO_FRACTION
        | \JSON_HEX_AMP
        | \JSON_HEX_APOS
        | \JSON_HEX_QUOT
        | \JSON_HEX_TAG
        | \JSON_PARTIAL_OUTPUT_ON_ERROR
        | \JSON_PRETTY_PRINT;

    /**
     * {@inheritdoc}
     */
    public function __invoke(\Throwable $exception, bool $displayErrorDetails): string
    {
        $output = ['message' => $exception->getMessage()];

        if ($displayErrorDetails) {
            $output['trace'] = [];

            do {
                $output['trace'][] = $this->renderException($exception);
            } while ($exception = $exception->getPrevious());
        }

        return (string) \json_encode(['error' => $output], static::JSON_ENCODE_OPTIONS);
    }

    /**
     * @param \Throwable $exception
     *
     * @return mixed[]
     */
    private function renderException(\Throwable $exception): array
    {
        return [
            'type' => \get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }
}
