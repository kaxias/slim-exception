<?php

/*
 * slim-exception (https://github.com/juliangut/slim-exception).
 * Slim HTTP exceptions and exception handling.
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-exception
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

declare(strict_types=1);

namespace Jgut\Slim\Exception\Handler\Whoops;

use Jgut\Slim\Exception\HttpExceptionManager;
use Slim\App;
use Whoops\Exception\FrameCollection;
use Whoops\Exception\Inspector;

/**
 * Whoops dumper helper trait.
 */
trait DumperTrait
{
    /**
     * Get array data from exception.
     *
     * @param Inspector $inspector
     * @param bool      $addTrace
     *
     * @return array
     */
    protected function getExceptionData(Inspector $inspector, bool $addTrace = false): array
    {
        /* @var \Jgut\Slim\Exception\HttpException $exception */
        $exception = $inspector->getException();

        $error = [
            'id' => $exception->getIdentifier(),
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
        ];

        if ($addTrace) {
            $error['trace'] = $this->getExceptionStack($inspector);
        }

        return $error;
    }

    /**
     * Get exception stack trace.
     *
     * @param Inspector $inspector
     *
     * @return array
     */
    protected function getExceptionStack(Inspector $inspector): array
    {
        $frames = $this->filterInternalFrames($inspector->getFrames());

        $exceptionStack = [];
        foreach ($frames as $frame) {
            $exceptionStack[] = [
                'file' => $frame->getFile(),
                'line' => $frame->getLine(),
                'function' => $frame->getFunction(),
                'class' => $frame->getClass(),
                'args' => $frame->getArgs(),
            ];
        }

        return $exceptionStack;
    }

    /**
     * Filter frames to remove HTTP Exception management classes.
     *
     * @param FrameCollection $frames
     *
     * @return FrameCollection
     */
    protected function filterInternalFrames(FrameCollection $frames): FrameCollection
    {
        /* @var \Whoops\Exception\Frame[] $frameList */
        $frameList = $frames->getArray();
        $firstNonInternal = $this->getFirstNonInternalFrame($frameList);

        $frames = new FrameCollection([]);
        $frames->prependFrames(array_values(array_slice($frameList, $firstNonInternal)));

        return $frames;
    }

    /**
     * Find position of the first non internal frame.
     *
     * @param \Whoops\Exception\Frame[] $frames
     *
     * @return int
     */
    protected function getFirstNonInternalFrame(array $frames): int
    {
        $excludedPathRegex = sprintf('!^%s/!', dirname(__DIR__, 2));

        $firstFrame = 0;
        for ($i = 0, $length = count($frames); $i < $length; $i++) {
            $frame = $frames[$i];
            $frameCallback = sprintf('%s::%s', $frame->getClass(), $frame->getFunction());

            if ($frameCallback === HttpExceptionManager::class . '::Jgut\Slim\Exception\{closure}'
                || preg_match($excludedPathRegex, $frame->getFile())
            ) {
                continue;
            }

            if ($frameCallback === App::class . '::__invoke') {
                // notFoundHandler/notAllowedHandler directly called by \Slim\App::__invoke. Display manager handling
                $firstFrame = $i - 1;
                break;
            }

            $nextFrame = $frames[$i + 1];
            $nextFrameCallback = sprintf('%s::%s', $nextFrame->getClass(), $nextFrame->getFunction());

            if ($nextFrameCallback === App::class . '::handleException') {
                // Exception captured by \Slim\App::handleException. Skip Slim's handling
                $firstFrame = $i + 2;
                break;
            }

            $firstFrame = $i;
            break;
        }

        return $firstFrame;
    }
}
