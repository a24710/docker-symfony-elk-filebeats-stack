<?php


namespace App\Entity;

use App\Utils\IDGenerator;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\SerializedName;


abstract class BaseEntity
{
    public const _ELASTIC_WRITE_GROUP = 'elastic:write';
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @ApiProperty(identifier=false)
     **/
    protected int $id;

    /**
     * @ORM\Column(type="string", length=36, nullable=false, unique=true)
     * @ApiProperty(identifier=true)
     */
    protected ?string $uuid;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"base"})
     */
    protected ?\DateTime $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"base"})
     */
    protected ?\DateTime $updatedAt;

    public function __construct()
    {
        $this->id = 0;
        $this->uuid = null;
        $this->createdAt = null;
        $this->updatedAt = null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function isPersisted(): bool
    {
        return ($this->id !== 0);
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /** @ORM\PrePersist() */
    public function prePersist()
    {
        $this->uuid = IDGenerator::generateUUID();
    }
}



