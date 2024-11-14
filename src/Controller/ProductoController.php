<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ProductoController extends AbstractController
{
    #[Route('/productos', name: 'app_productos', methods: ['GET'])]
    public function index(ProductoRepository $productoRepository): Response
    {
        return $this->convertToJson($productoRepository->findAll());
    }


    #[Route('/productos/tipo/{tipo}', name: 'app_get_productos_por_tipo', methods: ['GET'])]
    public function getProductosPorTipo(ProductoRepository $productoRepository, string $tipo): JsonResponse
    {
        // Obtener productos filtrados por tipo
        $productos = $productoRepository->findBy(['tipo_producto' => $tipo]);
        //dd($productos);
        // Utilizar convertToJson para formatear la respuesta JSON
        return $this->convertToJson($productos);
    }



    #[Route('/api/anadirProducto', name: 'app_add_producto', methods: ['POST'])]
    #[IsGranted("ROLE_ADMIN")]
    public function addProducto(Request $request, ProductoRepository $productoRepository): JsonResponse
    {
        // Decodificamos el contenido de la solicitud JSON
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            throw new NotFoundHttpException('Faltan parametros');
        }

        // Como nos han enseñado en clase es insertado los datos a traves del constructor, pero bueno
        $producto = new Producto();
        $producto->setNombre($data['nombre']);
        $producto->setDescripcion($data['descripcion']);
        $producto->setStock($data['stock']);
        $producto->setImagen($data['imagen']);
        $producto->setPrecio($data['precio']);
        $producto->setTipoProducto($data['tipo_producto']);

        // Persistimos el producto en la base de datos
        $productoRepository->anadirProducto($producto, true);

        // Retornamos la respuesta con los datos del producto creado
        return new JsonResponse([
            'status' => 'Producto añadido',
            'producto' => [
                'id' => $producto->getId(),
                'nombre' => $producto->getNombre(),
                'descripcion' => $producto->getDescripcion(),
                'stock' => $producto->getStock(),
                'imagen' => $producto->getImagen(),
                'precio' => $producto->getPrecio(),
                'tipo_producto' => $producto->getTipoProducto(),
            ]
        ], Response::HTTP_CREATED);
    }


    #[Route('/api/producto/delete/{id}', name: 'app_producto_delete', methods: ['DELETE'])]
    #[IsGranted("ROLE_ADMIN")]
    public function deleteProducto(int $id, ProductoRepository $productoRepository): Response
    {
        $producto = $productoRepository->find($id);

        if (!$producto) {
            return new JsonResponse(['status' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $productoRepository->deleteProducto($producto);

        return new JsonResponse(['status' => 'Producto eliminado'], Response::HTTP_OK);
    }


    #[Route('/api/producto/editar/{id}', name: 'app_producto_editar', methods: ['PUT'])]
    #[IsGranted("ROLE_ADMIN")]
    public function editProducto(int $id, Request $request, ProductoRepository $productoRepository): JsonResponse
    {
        $producto = $productoRepository->find($id);

        if (!$producto) {
            return new JsonResponse(['status' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        //dd($data);

        if (empty($data)) {
            throw new NotFoundHttpException('Faltan parametros');
        }

        $producto->setNombre($data['nombre']);
        $producto->setDescripcion($data['descripcion']);
        $producto->setPrecio($data['precio']);
        $producto->setStock($data['stock']);
        $producto->setImagen($data['imagen']);    
        $producto->setTipoProducto($data['tipo_producto']);

        $productoRepository->anadirProducto($producto);

        return new JsonResponse([
            'status' => 'Producto actualizado exitosamente',
            'producto' => [
                'id' => $producto->getId(),
                'nombre' => $producto->getNombre(),
                'descripcion' => $producto->getDescripcion(),
                'stock' => $producto->getStock(),
                'imagen' => $producto->getImagen(),
                'precio' => $producto->getPrecio(),
                'tipo_producto' => $producto->getTipoProducto(),
            ]
        ], Response::HTTP_OK);
    }


    private function convertToJson($data): JsonResponse
    {
        // Configuramos los encoders y normalizadores
        $encoders = [new JsonEncoder()];
        $defaultContext = [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
            // Ignoramos la propiedad productoSolicitados para evitar serializar colecciones innecesarias
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['productoSolicitados'],
            // Evitamos problemas de referencia circular
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            }
        ];
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];

        // Creamos el servicio Serializer
        $serializer = new Serializer($normalizers, $encoders);

        // Normalizamos y serializamos los datos en formato JSON
        $normalized = $serializer->normalize($data);
        $jsonContent = $serializer->serialize($normalized, 'json');

        // Retornamos la respuesta en formato JSON
        return JsonResponse::fromJsonString($jsonContent, 200);
    }



}
