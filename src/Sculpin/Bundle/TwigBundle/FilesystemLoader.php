<?php

declare(strict_types=1);

/*
 * This file is a part of Sculpin.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sculpin\Bundle\TwigBundle;

use Sculpin\Core\Event\SourceSetEvent;
use Sculpin\Core\Sculpin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

final class FilesystemLoader extends TwigFilesystemLoader implements EventSubscriberInterface
{
    /** @var string[] */
    private $extensions;

    public function __construct(array $paths = [], string $rootPath = null, array $extensions = [])
    {
        parent::__construct($paths, $rootPath);
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Sculpin::EVENT_BEFORE_RUN => 'beforeRun',
        ];
    }

    public function beforeRun(SourceSetEvent $sourceSetEvent): void
    {
        if ($sourceSetEvent->sourceSet()->newSources()) {
            // invalidate the cache
            $this->cache = $this->errorCache = [];
        }
    }

    /**
     * {@inheritdoc}
     * @throws LoaderError
     */
    protected function findTemplate($name, $throw = true)
    {
        try {
            return $this->findTemplateWithExtensions($name, $throw);
        } catch (LoaderError $e) {
            if ('' === $name || '@' === $name[0] || !preg_match('/^(?P<bundle>[^:]*?)(?:Bundle)?:(?P<path>[^:]*+):(?P<template>.+\.[^\.]+\.[^\.]+)$/', $name, $m)) {
                throw $e;
            }
            if ('' !== $m['path']) {
                $m['template'] = $m['path'].'/'.$m['template'];
            }
            if ('' !== $m['bundle']) {
                $suggestion = '@'.$m['bundle'].'/'.$m['template'];
            } else {
                $suggestion = $m['template'];
            }
            if (false === $this->findTemplateWithExtensions($suggestion, false)) {
                throw $e;
            }
            throw new LoaderError(sprintf('Template reference "%s" not found, did you mean "%s"?', $name, $suggestion), -1, null, $e);
        }
    }

    /**
     * @param string $name
     * @return false|string
     * @throws LoaderError
     */
    protected function findTemplateWithExtensions($name, $throw = true)
    {
        $toThrow = null;
        foreach ($this->extensions as $ext) {
            $suggestion = $ext ? $name . '.'.$ext : $name;
            try {
                $template = parent::findTemplate($suggestion, $throw);
                if ($template !== false) {
                    return $template;
                }
            } catch (LoaderError $e) {
                $toThrow = $toThrow ?: $e;
            }
        }
        if ($throw) {
            throw $toThrow;
        }

        return false;
    }
}
