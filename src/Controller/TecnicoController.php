<?php

namespace App\Controller;

use App\Entity\Producto;
use App\Repository\ProductoRepository;
use App\Repository\RepacionRepository;
use App\Repository\UsuarioRepository;
use App\Repository\SolicitudRepository;
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

    //FUNCIONA
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

        //redirijir a la ventana de tecnicos y pasarle el token JWT y la información del usuario
        return $this->render('tecnico.html.twig', [
            'token' => $token,
            'rol' => $usuario->getRol(),
            'nombre' => $usuario->getNombre(),
            'productos' => $productos
        ]);
    }

    //FUNCIONA
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

    //FUNCIONA
    #[Route('/getReparaciones', name: 'app_get_reparaciones', methods: ['GET'])]
    public function getReparaciones(RepacionRepository $reparacionRepository): JsonResponse
    {
        
        $reparaciones = $reparacionRepository->findAll();

        // Formatear las reparaciones en un array para la respuesta JSON
        $reparacionesArray = [];
        foreach ($reparaciones as $reparacion) {
            $productosSolicitados = $reparacion->getProductoSolicitados();
            $productosArray = [];
            foreach ($productosSolicitados as $productoSolicitado) {
                $producto = $productoSolicitado->getIdProducto();
                if ($producto->getTipoProducto() === 'consolas') {
                    $productosArray[] = [
                        'id' => $producto->getId(),
                        'nombre' => $producto->getNombre(),
                        'descripcion' => $producto->getDescripcion(),
                        'precio' => $producto->getPrecio(),
                        'stock' => $producto->getStock(),
                        'imagen' => $producto->getImagen(),
                        'tipo_producto' => $producto->getTipoProducto()
                    ];
                }
            }

            $nombreProducto = !empty($productosArray) ? $productosArray[0]['nombre'] : null;

            if (!empty($productosArray)) {
                $reparacionesArray[] = [
                    'id' => $reparacion->getId(),
                    'nombre_producto' => $nombreProducto,
                    'incidencia' => $reparacion->getIncidencia(),
                    'fecha_inicio' => $reparacion->getFechaInicio()->format('Y-m-d'),
                    'productos' => $productosArray
                ];
            }
        }

        // Devolver las reparaciones en una respuesta JSON
        return new JsonResponse($reparacionesArray, Response::HTTP_OK);
    }

    //FUNCIONA
    #[Route('/getReparacionesSmartphones', name: 'app_get_reparaciones_smartphones', methods: ['GET'])]
    public function getReparacionesSmartphones(RepacionRepository $reparacionRepository): JsonResponse
    {
        $reparaciones = $reparacionRepository->findAll();

        // Formatear las reparaciones en un array para la respuesta JSON
        $reparacionesArray = [];
        foreach ($reparaciones as $reparacion) {
            $productosSolicitados = $reparacion->getProductoSolicitados();
            $productosArray = [];
            foreach ($productosSolicitados as $productoSolicitado) {
                $producto = $productoSolicitado->getIdProducto();
                if ($producto->getTipoProducto() === 'smartphones') {
                    $productosArray[] = [
                        'id' => $producto->getId(),
                        'nombre' => $producto->getNombre(),
                        'descripcion' => $producto->getDescripcion(),
                        'precio' => $producto->getPrecio(),
                        'stock' => $producto->getStock(),
                        'imagen' => $producto->getImagen(),
                        'tipo_producto' => $producto->getTipoProducto()
                    ];
                }
            }

            $nombreProducto = !empty($productosArray) ? $productosArray[0]['nombre'] : null;

            if (!empty($productosArray)) {
                $reparacionesArray[] = [
                    'id' => $reparacion->getId(),
                    'nombre_producto' => $nombreProducto,
                    'incidencia' => $reparacion->getIncidencia(),
                    'fecha_inicio' => $reparacion->getFechaInicio()->format('Y-m-d'),
                    'productos' => $productosArray
                ];
            }
        }

        // Devolver las reparaciones en una respuesta JSON
        return new JsonResponse($reparacionesArray, Response::HTTP_OK);
    }

    //FUNCIONA
    #[Route('/getReparacion/{id}', name: 'app_get_reparacion', methods: ['GET'])]
    public function getReparacion(RepacionRepository $repacionRepository, $id): JsonResponse
    {
        // Buscar la reparación por ID
        $reparacion = $repacionRepository->find($id);
    
        if (!$reparacion) {
            return new JsonResponse(['status' => 'Reparación no encontrada'], Response::HTTP_NOT_FOUND);
        }
    
        $productosSolicitados = $reparacion->getProductoSolicitados();
    
        // Crear un array con los productos solicitados
        $productos = [];
        foreach ($productosSolicitados as $productoSolicitado) {
            $producto = $productoSolicitado->getIdProducto();
            $productos[] = [
                'nombre' => $producto->getNombre(),
                'tipo_producto' => $producto->getTipoProducto()
            ];
        }
        // Devolver la incidencia y la fecha de inicio en una respuesta JSON
        return new JsonResponse([
            'incidencia' => $reparacion->getIncidencia(),
            'fecha_inicio' => $reparacion->getFechaInicio()->format('Y-m-d'),
            'productos_solicitados' => $productos
        ], Response::HTTP_OK);
    }
    


    //PROBAR    
    #[Route('/gestionarReparacion/{id}', name: 'app_gestionar_reparacion', methods: ['POST'])]
    public function gestionarReparacion(Request $request, SolicitudRepository $solicitudRepository, RepacionRepository $reparacionRepository, int $id): JsonResponse
    {
        // Buscar la reparación y solicitud por ID
        $reparacion = $reparacionRepository->find($id);
        $solicitud = $solicitudRepository->find($id);
    
        // Verificar que la reparación existe
        if (!$reparacion) {
            return new JsonResponse(['status' => 'Reparación no encontrada'], Response::HTTP_NOT_FOUND);
        }
    
        // Decodificar los datos JSON del cuerpo de la solicitud
        $data = json_decode($request->getContent(), true);
        dd($data);
        // Verificar si los datos fueron decodificados correctamente
        if (!$data) {
            return new JsonResponse(['status' => 'Datos no válidos'], Response::HTTP_BAD_REQUEST);
        }
    
        // Establecer la fecha de finalización y el precio de la reparación
        $reparacion->setFechaFin(new \DateTime($data['fecha_fin']));
        $reparacionRepository->anadirRepacion($reparacion);
    
        $solicitud->setPrecioSolicitud($data['precio_solicitud']);
        $solicitudRepository->anadirPrecioReparacion($solicitud);
    
        // Devolver respuesta de éxito
        return new JsonResponse(['status' => 'Reparación finalizada'], Response::HTTP_OK);
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
