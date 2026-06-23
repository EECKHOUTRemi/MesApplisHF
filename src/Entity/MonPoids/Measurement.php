<?php

namespace App\Entity\MonPoids;

use App\Entity\User;
use App\Repository\MonPoids\MeasurementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mensuration corporelle MonPoids : poitrine, hanches, cuisse et taille à une date donnée.
 * Au moins une valeur doit être renseignée (validé au niveau du formulaire).
 */
#[ORM\Entity(repositoryClass: MeasurementRepository::class)]
class Measurement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?float $chest = null;

    #[ORM\Column(nullable: true)]
    private ?float $hips = null;

    #[ORM\Column(nullable: true)]
    private ?float $thigh = null;

    #[ORM\Column(nullable: true)]
    private ?float $waist = null;

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
    public function getChest(): ?float
    {
        return $this->chest;
    }

    /**
     * @param float|null $chest
     * @return static
     */
    public function setChest(?float $chest): static
    {
        $this->chest = $chest;

        return $this;
    }

    /** @return float|null */
    public function getHips(): ?float
    {
        return $this->hips;
    }

    /**
     * @param float|null $hips
     * @return static
     */
    public function setHips(?float $hips): static
    {
        $this->hips = $hips;

        return $this;
    }

    /** @return float|null */
    public function getThigh(): ?float
    {
        return $this->thigh;
    }

    /**
     * @param float|null $thigh
     * @return static
     */
    public function setThigh(?float $thigh): static
    {
        $this->thigh = $thigh;

        return $this;
    }

    /** @return float|null */
    public function getWaist(): ?float
    {
        return $this->waist;
    }

    /**
     * @param float|null $waist
     * @return static
     */
    public function setWaist(?float $waist): static
    {
        $this->waist = $waist;

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
