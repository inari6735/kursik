<?php declare(strict_types=1);

namespace App\Course\Application;

/**
 * Decodes the Editor.js JSON submitted by the form into the array stored
 * on the Course entity. Blank input means "no content".
 */
final class EditorJsContent
{
    public static function decode(?string $content): ?array
    {
        if (null === $content || '' === trim($content)) {
            return null;
        }

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }
}
