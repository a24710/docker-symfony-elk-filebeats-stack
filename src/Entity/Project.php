<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(indexes={@ORM\Index(columns={"uuid"})})
 */
class Project extends BaseEntity
{
    /**
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
     * @Groups({"project:read", BaseEntity::_ELASTIC_WRITE_GROUP})
     **/
    protected ?string $name;

    public function __construct()
    {
        parent::__construct();
        $this->name = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Project
    {
        $this->name = $name;
        return $this;
    }

}