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

namespace Jgut\Slim\Exception\Whoops\Renderer;

use Jgut\Slim\Exception\Whoops\Inspector;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class HtmlRenderer extends PrettyPageHandler
{
    use RendererTrait;

    public function __construct(string $defaultTitle = 'Slim Application error')
    {
        parent::__construct();

        $this->defaultTitle = $defaultTitle;
        $this->setPageTitle($defaultTitle);
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        $exception = $this->getException();
        $this->setInspector(new Inspector($exception));

        return parent::handle() ?? Handler::QUIT;
    }
}
