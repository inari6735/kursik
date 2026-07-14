<?php declare(strict_types=1);

namespace App\Access\Domain;

/**
 * THE permission catalog — the single source of truth for what permissions exist.
 * The admin panel renders checkboxes from cases(); it never creates permissions.
 */
enum Permission: string
{
    case CourseCreate = 'course.create';
    case CourseRename = 'course.rename';
    case CoursePublish = 'course.publish';
    case AccessManage = 'access.manage';

    public function label(): string
    {
        return match ($this) {
            self::CourseCreate => 'Create courses',
            self::CourseRename => 'Rename courses',
            self::CoursePublish => 'Publish courses',
            self::AccessManage => 'Manage users and roles',
        };
    }

    /**
     * Cases grouped by their prefix ('course', 'access') — the matrix layout.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[explode('.', $permission->value)[0]][] = $permission;
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $permission): string => $permission->value, self::cases());
    }
}
