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

        // Utilizar convertToJson para formatear la respuesta JSON
        return $this->convertToJson($productos);
    }

    #[Route('/productos', name: 'app_add_producto', methods: ['POST'])]
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
            'status' => 'Producto creado exitosamente',
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


    #[Route('/producto/delete/{id}', name: 'app_producto_delete', methods: ['DELETE'])]
    public function deleteProducto(int $id, ProductoRepository $productoRepository): Response
    {
        $producto = $productoRepository->find($id);

        if (!$producto) {
            return new JsonResponse(['status' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $entityManager = $productoRepository->getEntityManager();

        $productoRepository->deleteProducto($producto);

        // Flushear los cambios en la base de datos
        $entityManager->flush();

        // Responder con éxito
        return new JsonResponse(['status' => 'Producto eliminado'], Response::HTTP_OK);
    }




    private function convertToJson($data): JsonResponse
    {
        // Configuramos los encoders y normalizadores
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer()];

        // Creamos el servicio Serializer
        $serializer = new Serializer($normalizers, $encoders);

        // Normalizamos los datos
        $normalized = $serializer->normalize($data, null, [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId(); // Para evitar problemas de referencia circular
            }
        ]);

        // Convertimos los datos normalizados a JSON
        $jsonContent = $serializer->serialize($normalized, 'json');

        // Retornamos la respuesta en formato JSON
        return JsonResponse::fromJsonString($jsonContent, 200);
    }


}