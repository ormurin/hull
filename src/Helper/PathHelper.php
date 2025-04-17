<?php
namespace Ormurin\Hull\Helper;

use Ormurin\Hull\Engine\Env;

class PathHelper
{
    public static function getAbsolutePath(string $relativePath, array $searchInDirs = [], ?string $currentDir = null): string|false
    {
        $absolutePaths = [];
        $env = Env::instance();
        if ( !strlen($relativePath) ) {
            throw new \ValueError("Relative path is empty.");
        }
        $relativePath = str_replace('\\', '/', $relativePath);
        if ( str_starts_with($relativePath, "/") ) {
            $baseDir = $env->getRootDir();
            if ( is_null($baseDir) ) {
                throw new \RuntimeException("Root directory is not set.");
            }
            $relativePath = ltrim($relativePath, "/");
        } else if ( str_starts_with($relativePath, "~/") ) {
            $baseDir = $env->getHomeDir();
            if ( is_null($baseDir) ) {
                throw new \RuntimeException("Home directory is not set.");
            }
            $relativePath = ltrim($relativePath, "~/");
        } else if ( str_starts_with($relativePath, "~") ) {
            $baseDir = $env->getUnitsDir();
            if ( is_null($baseDir) ) {
                throw new \RuntimeException("Units directory is not set.");
            }
            $relativePath = ltrim($relativePath, "~");
        } else if ( str_starts_with($relativePath, '@') ) {
            $baseDir = $env->getAppDir();
            if ( is_null($baseDir) ) {
                throw new \RuntimeException("App directory is not set.");
            }
            $relativePath = ltrim($relativePath, "@/");
        } else {
            $baseDir = $env->getWorkDir();
            $relativePath = ltrim($relativePath, "/");
            if ( !is_null($currentDir) ) {
                if ( !strlen($currentDir) || !file_exists($currentDir) || !is_dir($currentDir) ) {
                    throw new \ValueError("Invalid current directory: $currentDir");
                }
                $currentDir = rtrim($currentDir, "/\\");
                if ( $currentDir !== '' && $currentDir !== $baseDir && $relativePath !== '' ) {
                    $absolutePaths[] = "$currentDir/$relativePath";
                }
            } else if ( is_null($baseDir) ) {
                throw new \RuntimeException("Working directory is not set.");
            }
        }

        if ( (string)$baseDir !== '' && $relativePath !== '' ) {
            $absolutePaths[] = "$baseDir/$relativePath";
            foreach ( $searchInDirs as $searchInDir ) {
                $absolutePaths[] = "$baseDir/$searchInDir/$relativePath";
            }
        }

        foreach ( $absolutePaths as $absolutePath ) {
            if ( file_exists($absolutePath) ) {
                return realpath($absolutePath);
            }
        }

        return false;
    }

}