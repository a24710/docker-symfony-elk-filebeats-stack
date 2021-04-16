<?php


namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Validators as Validators;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\EmployeeRepository;

/**
 * @ORM\Entity(repositoryClass=EmployeeRepository::class)
 * @Validators\EmployeeConstraint()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(indexes={@ORM\Index(columns={"uuid"})})
 */
class Employee extends BaseEntity
{
    public const _POSITION_CONTRACTOR = 'contractor';
    public const _POSITION_EMPLOYEE = 'employee';
    public const _POSITION_MANAGER = 'manager';
    public const _POSITION_DIRECTOR = 'director';

    /**
    * @ORM\Column(type="string", length=255, nullable=false)
    * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
    * @Assert\NotBlank()
    **/
    protected ?string $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
     * @Assert\NotBlank()
     **/
    protected ?string $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
     * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
     * @Assert\NotBlank()
     * @Assert\Email()
     **/
    protected ?string $email;

    /**
     * @ORM\Column(type="float", length=255, nullable=true)
     * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
     **/
    protected ?float $salary;

    /**
     * @ORM\Column(type="float", length=255, nullable=true)
     * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
     **/
    protected ?float $bonus;

    /**
     * @ORM\Column(type="float", length=255, nullable=true)
     * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
     **/
    protected ?float $insuranceAmount;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
     */
    protected ?\DateTime $employmentStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
     */
    protected ?\DateTime $employmentEndDate;

    /**
     * One employee belongs to just one company
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="employees")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * @Groups({"employee:read", "employee:write"})
     */
    protected ?Company $company;

    /**
     * @ORM\Column(type="string", length=32, nullable=false)
     * @Groups({"employee:read", "employee:write", BaseEntity::_ELASTIC_WRITE_GROUP})
     **/
    protected ?string $positionInCompany;

    /**
     * @ORM\OneToMany(targetEntity="EmployeeProjectRelation", mappedBy="employee", cascade={"persist", "remove"})
     */
    protected $projectRelations;

    public function __construct()
    {
        parent::__construct();

        $this->firstName = null;
        $this->lastName = null;
        $this->email = null;
        $this->salary = null;
        $this->bonus = null;
        $this->insuranceAmount = null;
        $this->employmentStartDate = null;
        $this->employmentEndDate = null;
        $this->company = null;
        $this->positionInCompany = null;
        $this->projectRelations = new ArrayCollection();
    }

    public static function availablePositions(): array
    {
        return [self::_POSITION_CONTRACTOR,
                self::_POSITION_DIRECTOR,
                self::_POSITION_EMPLOYEE,
                self::_POSITION_MANAGER];
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): Employee
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): Employee
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): Employee
    {
        $this->email = $email;
        return $this;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function setSalary(?float $salary): Employee
    {
        $this->salary = $salary;
        return $this;
    }

    public function getBonus(): ?float
    {
        return $this->bonus;
    }

    public function setBonus(?float $bonus): Employee
    {
        $this->bonus = $bonus;
        return $this;
    }

    public function getInsuranceAmount(): ?float
    {
        return $this->insuranceAmount;
    }

    public function setInsuranceAmount(?float $insuranceAmount): Employee
    {
        $this->insuranceAmount = $insuranceAmount;
        return $this;
    }

    public function getEmploymentStartDate(): ?\DateTime
    {
        return $this->employmentStartDate;
    }

    public function setEmploymentStartDate(?\DateTime $employmentStartDate): Employee
    {
        $this->employmentStartDate = $employmentStartDate;
        return $this;
    }

    public function getEmploymentEndDate(): ?\DateTime
    {
        return $this->employmentEndDate;
    }

    public function setEmploymentEndDate(?\DateTime $employmentEndDate): Employee
    {
        $this->employmentEndDate = $employmentEndDate;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): Employee
    {
        $this->company = $company;
        return $this;
    }

    public function getPositionInCompany(): ?string
    {
        return $this->positionInCompany;
    }

    public function setPositionInCompany(?string $positionInCompany): Employee
    {
        $this->positionInCompany = $positionInCompany;
        return $this;
    }

    public function getProjectRelations()
    {
        return $this->projectRelations;
    }

    public function setProjectRelations($projectRelations): Employee
    {
        $this->projectRelations = $projectRelations;
        return $this;
    }

    public function addProjectRelation(EmployeeProjectRelation $relation): Employee
    {
        if (!$this->projectRelations->contains($relation)){
            $this->projectRelations->add($relation);
        }

        return $this;
    }
}







