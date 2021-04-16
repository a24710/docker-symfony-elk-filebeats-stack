<?php


namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(indexes={@ORM\Index(columns={"uuid"})})
 */
class Company extends BaseEntity
{
    /**
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
     * @Groups({"company:read", BaseEntity::_ELASTIC_WRITE_GROUP})
     **/
    protected ?string $name;

    /**
     * One company has many employees
     * @ORM\OneToMany(targetEntity="Employee", mappedBy="company")
     */
    protected $employees;

    public function __construct()
    {
        parent::__construct();
        $this->name = null;
        $this->employees = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Company
    {
        $this->name = $name;
        return $this;
    }

    public function getEmployees()
    {
        return $this->employees;
    }

    public function setEmployees($employees): Company
    {
        $this->employees = $employees;
        return $this;
    }
}



