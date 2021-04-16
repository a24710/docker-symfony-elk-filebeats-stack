<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class EmployeeProjectRelation extends BaseEntity
{
    public const _EMPLOYEE_ROLE_DEVELOPER = 'developer';
    public const _EMPLOYEE_ROLE_DESIGNER = 'designer';
    public const _EMPLOYEE_ROLE_MANAGER = 'manager';
    public const _EMPLOYEE_ROLE_SALES_PERSON = 'sales_person';

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"employee_project_relation:read"})
     */
    protected ?string $role;

    /**
     * @ORM\ManyToOne(targetEntity="Employee")
     * @ORM\JoinColumn(name="employee_id", referencedColumnName="id", nullable=false)
     * @Groups({"employee_project_relation:read"})
     */
    protected ?Employee $employee;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     * @Groups({"employee_project_relation:read"})
     */
    protected ?Project $project;

    public function __construct()
    {
        parent::__construct();
        $this->role = null;
    }

    public static function availableRoles(): array
    {
        return [self::_EMPLOYEE_ROLE_DESIGNER,
            self::_EMPLOYEE_ROLE_DEVELOPER,
            self::_EMPLOYEE_ROLE_MANAGER,
            self::_EMPLOYEE_ROLE_SALES_PERSON];
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): EmployeeProjectRelation
    {
        $this->role = $role;
        return $this;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): EmployeeProjectRelation
    {
        $this->employee = $employee;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): EmployeeProjectRelation
    {
        $this->project = $project;
        return $this;
    }
}



