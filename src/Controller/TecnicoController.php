<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use App\Repository\RepacionRepository;
use App\Repository\UsuarioRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class TecnicoController extends AbstractController
{
    //login de tecnicos
    #[Route(name: 'default')]
    public function index()
    {
        //$tecnico = $usuarioRepository->findBy(['tipo' => 'tecnico']);

        return $this->render('login.html.twig');
    }

    //comprobar datos del login de aplicacion de tecnicos
    #[Route('/comprobarLogin', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, UsuarioRepository $usuarioRepository, ProductoRepository $productoRepository, JWTTokenManagerInterface $jwtManager): Response 
    {

        $data = $_POST;

        if (empty($data['email']) || empty($data['password'])) {
            return $this->render('login.html.twig', [
                'error' => 'Faltan parámetros'
            ]);
        }

        // Buscar el usuario por email
        $usuario = $usuarioRepository->findOneBy(['email' => $data['email']]);

        $productos = $productoRepository->findAll();
        
        if (!$usuario) {
            return $this->render('login.html.twig', [
                'error' => 'Usuario no encontrado'
            ]);
        }

        // Comparar la contraseña directamente (sin hash)
        if ($usuario->getPassword() !== $data['password']) {
            return $this->render('login.html.twig', [
                'error' => 'Credenciales inválidas'
            ]);
        }

        // Verificar si el rol del usuario es técnico autorizado
        if (!in_array($usuario->getRol(), ['tecnicoC', 'tecnicoM'])) {
            return $this->render('login.html.twig', [
                'error' => 'Persona no autorizada'
            ]);
        }

        // Generar el token JWT
        $token = $jwtManager->create($usuario);

        // Renderizar la plantilla y pasarle el token JWT y la información del usuario
        return $this->render('tecnico.html.twig', [
            'token' => $token,
            'rol' => $usuario->getRol(),
            'nombre' => $usuario->getNombre(),
            'productos' => $productos
        ]);
    }

    //mostrar la vista de tecnicos
    #[Route('/hidden',name: 'tecnico')]
    public function tabs(): Response
    {
        return $this->render('tecnico.html.twig');
    }


   /* #[Route('/gestionarReparacion/{id}', name: 'app_gestionar_reparacion', methods: ['POST'])]
    public function gestionarReparacion(Request $request, RepacionRepository $reparacionRepository, SolicitudRepository $solitud, EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $fechaFin = $request->request->get('fecha_fin');
        $precioSolicitud = $request->request->get('precio_solicitud');

        // Verificar si los datos de la reparacion no estan vacios
        if (empty($fechaFin) || empty($precioSolicitud)) {
            return new JsonResponse(['status' => 'Faltan parametros'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar la reparacion por id
        $reparacion = $reparacionRepository->findOneBy(['id' => $id]);
        $solicitud = $solitud->findOneBy(['id' => $id]);

        if ($reparacion == null) {
            return new JsonResponse(['status' => 'Reparacion no encontrada'], Response::HTTP_NOT_FOUND);
        }

        // Establecer la fecha de finalización proporcionada por el usuario
        $reparacion->setFechaFin(new \DateTime($fechaFin));
        $solicitud->setPrecioSolicitud($precioSolicitud);

        $entityManager->persist($reparacion);
        $entityManager->persist($solicitud);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Reparacion actualizada'], Response::HTTP_OK);
    }*/

    #[Route('/getProducto/{id}', name: 'app_get_product', methods: ['GET'])]
    public function getProduct(ProductoRepository $productoRepository, $id): JsonResponse
    {
        $producto = $productoRepository->find($id);

        if (!$producto) {
            return new JsonResponse(['status' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'nombre' => $producto->getNombre(),
            'descripcion' => $producto->getDescripcion(),
            'precio' => $producto->getPrecio(),
            'stock' => $producto->getStock(),
            'imagen' => $producto->getImagen(),
            'tipo_producto' => $producto->getTipoProducto()
        ], Response::HTTP_OK);
    }

    //obtener reparaciones
    #[Route('/getReparaciones', name: 'app_get_reparaciones', methods: ['GET'])]
    public function getReparaciones(RepacionRepository $reparacionRepository): Response
    {
        return $this->convertToJson($reparacionRepository->findAll());
    }
    

    //PROBAR CUANDO SE HAGAN REPARACIONES
    //metodo para obtener los productos solicitados para reparación
    #[Route('/getProductosSolicitadosParaReparacion', name: 'app_get_productos_solicitados_para_reparacion', methods: ['GET'])]
    public function getProductosSolicitadosParaReparacion(RepacionRepository $reparacionRepository): JsonResponse
    {
        $reparaciones = $reparacionRepository->findAll();
        $productosSolicitados = [];

        foreach ($reparaciones as $reparacion) {
            foreach ($reparacion->getProductoSolicitados() as $productoSolicitado) {
                $producto = $productoSolicitado->getProducto();
                $productosSolicitados[] = [
                    'id' => $producto->getId(),
                    'nombre' => $producto->getNombre(),
                    'descripcion' => $producto->getDescripcion(),
                    'precio' => $producto->getPrecio(),
                    'stock' => $producto->getStock(),
                    'imagen' => $producto->getImagen()
                ];
            }
        }

        return new JsonResponse($productosSolicitados, Response::HTTP_OK);
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
