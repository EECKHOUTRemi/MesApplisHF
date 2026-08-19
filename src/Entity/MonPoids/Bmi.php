<?php

namespace App\Entity\MonPoids;

use App\Entity\User;
use App\Repository\MonPoids\BmiRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Enregistrement d'IMC MonPoids : taille, poids et valeur calculée à une date donnée.
 */
#[ORM\Entity(repositoryClass: BmiRepository::class)]
class Bmi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    private ?float $height = null;

    #[ORM\Column]
    private ?float $weight = null;

    #[ORM\Column]
    private ?float $bmi = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return User|null */
    public function getuser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return static
     */
    public function setuser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /** @return float|null */
    public function getHeight(): ?float
    {
        return $this->height;
    }

    /**
     * @param float $height
     * @return static
     */
    public function setHeight(float $height): static
    {
        $this->height = $height;

        return $this;
    }

    /** @return float|null */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     * @return static
     */
    public function setWeight(float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /** @return float|null */
    public function getBmi(): ?float
    {
        return $this->bmi;
    }

    /**
     * Calcule et stocke l'IMC à partir de la taille en cm et du poids en kg, arrondi à 2 décimales.
     *
     * @param float $height Taille en centimètres
     * @param float $weight Poids en kilogrammes
     * @return static
     */
    public function setBmi(float $height, float $weight): static
    {
        $bmi = $weight / ($height / 100) ** 2;

        $this->bmi = round($bmi, 2);

        return $this;
    }

    /** @return \DateTimeImmutable|null */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeImmutable $createdAt
     * @return static
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
