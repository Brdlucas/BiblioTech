<?php

namespace App\Entity;

use App\Repository\BorrowingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BorrowingRepository::class)]
class Borrowing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $emprunted_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $rendered_at = null;

    #[ORM\ManyToOne(inversedBy: 'borrowings')]
    private ?Book $book = null;

    #[ORM\ManyToOne(inversedBy: 'borrowings')]
    private ?User $userbook = null;

    #[ORM\Column]
    private ?bool $is_verified = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmpruntedAt(): ?\DateTimeImmutable
    {
        return $this->emprunted_at;
    }

    public function setEmpruntedAt(\DateTimeImmutable $emprunted_at): static
    {
        $this->emprunted_at = $emprunted_at;

        return $this;
    }

    public function getRenderedAt(): ?\DateTimeImmutable
    {
        return $this->rendered_at;
    }

    public function setRenderedAt(\DateTimeImmutable $rendered_at): static
    {
        $this->rendered_at = $rendered_at;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }

    public function getUserbook(): ?User
    {
        return $this->userbook;
    }

    public function setUserbook(?User $userbook): static
    {
        $this->userbook = $userbook;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->is_verified;
    }

    public function setIsVerified(bool $is_verified): static
    {
        $this->is_verified = $is_verified;

        return $this;
    }
}
