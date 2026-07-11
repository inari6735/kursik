<?php declare(strict_types=1);

namespace App\Course\Domain;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}