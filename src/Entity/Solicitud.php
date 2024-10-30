<?php

namespace App\Entity;

use App\Repository\SolicitudRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SolicitudRepository::class)]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap(["solicitud" => Solicitud::class, "compra" => Compra::class, "alquiler" => Alquiler::class, "reparacion" => Repacion::class])]
class Solicitud
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $precio_solicitud = null;

    /**
     * @var Collection<int, Usuario>
     */
    #[ORM\OneToMany(targetEntity: Usuario::class, mappedBy: 'id_solicitud')]
    private Collection $usuarios;

    /**
     * @var Collection<int, ProductoSolicitado>
     */
    #[ORM\OneToMany(targetEntity: ProductoSolicitado::class, mappedBy: 'id_solicitud')]
    private Collection $productoSolicitados;

    public function __construct()
    {
        $this->usuarios = new ArrayCollection();
        $this->productoSolicitados = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrecioSolicitud(): ?float
    {
        return $this->precio_solicitud;
    }

    public function setPrecioSolicitud(?float $precio_solicitud): static
    {
        $this->precio_solicitud = $precio_solicitud;

        return $this;
    }

    /**
     * @return Collection<int, Usuario>
     */
    public function getUsuarios(): Collection
    {
        return $this->usuarios;
    }

    public function addUsuario(Usuario $usuario): static
    {
        if (!$this->usuarios->contains($usuario)) {
            $this->usuarios->add($usuario);
            $usuario->setIdSolicitud($this);
        }

        return $this;
    }

    public function removeUsuario(Usuario $usuario): static
    {
        if ($this->usuarios->removeElement($usuario)) {
            // set the owning side to null (unless already changed)
            if ($usuario->getIdSolicitud() === $this) {
                $usuario->setIdSolicitud(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductoSolicitado>
     */
    public function getProductoSolicitados(): Collection
    {
        return $this->productoSolicitados;
    }

    public function addProductoSolicitado(ProductoSolicitado $productoSolicitado): static
    {
        if (!$this->productoSolicitados->contains($productoSolicitado)) {
            $this->productoSolicitados->add($productoSolicitado);
            $productoSolicitado->setIdSolicitud($this);
        }

        return $this;
    }

    public function removeProductoSolicitado(ProductoSolicitado $productoSolicitado): static
    {
        if ($this->productoSolicitados->removeElement($productoSolicitado)) {
            // set the owning side to null (unless already changed)
            if ($productoSolicitado->getIdSolicitud() === $this) {
                $productoSolicitado->setIdSolicitud(null);
            }
        }

        return $this;
    }


}
