<?php

namespace App\Entity;

use App\Repository\SimulationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SimulationRepository::class)
 */
class Simulation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $gridWidth;

    /**
     * @ORM\Column(type="integer")
     */
    private $gridHeight;

    /**
     * @ORM\Column(type="integer")
     */
    private $population;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getGridWidth(): ?int
    {
        return $this->gridWidth;
    }

    public function setGridWidth(int $gridWidth): self
    {
        $this->gridWidth = $gridWidth;

        return $this;
    }

    public function getGridHeight(): ?int
    {
        return $this->gridHeight;
    }

    public function setGridHeight(int $gridHeight): self
    {
        $this->gridHeight = $gridHeight;

        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(int $population): self
    {
        $this->population = $population;

        return $this;
    }
}
