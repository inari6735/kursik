<?php declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\AbstractRefreshToken;

/**
 * Concrete refresh-token entity for gesdinet/jwt-refresh-token-bundle.
 * The bundle's model classes carry no ORM mapping — it lives here.
 */
#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\Index(name: 'idx_refresh_tokens_username', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'uniq_refresh_tokens_token', columns: ['refresh_token'])]
class RefreshToken extends AbstractRefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected int|string|null $id = null;

    #[ORM\Column(length: 128)]
    protected ?string $refreshToken = null;

    #[ORM\Column(length: 255)]
    protected ?string $username = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $valid = null;
}