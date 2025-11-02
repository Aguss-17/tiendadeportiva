<?php
require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../modelos/modelo_blog.php';

class ControladorBlog {
    private $modelo;
    private $postsPorPagina = 6;

    public function __construct() {
        global $conexion;
        $this->modelo = new ModeloBlog($conexion);
    }

    public function obtenerDatosBlog() {
        $paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $categoriaFiltro = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
        $ordenFiltro = isset($_GET['orden']) ? trim($_GET['orden']) : 'fecha_desc';

        $offset = ($paginaActual - 1) * $this->postsPorPagina;

        $filtros = [
            'posts_por_pagina' => $this->postsPorPagina,
            'offset' => $offset,
            'busqueda' => $busqueda,
            'categoria' => $categoriaFiltro,
            'orden' => $ordenFiltro
        ];

        $posts = $this->modelo->obtenerPosts($filtros);
        $totalPosts = $this->modelo->contarPosts($filtros);
        $totalPaginas = ceil($totalPosts / $this->postsPorPagina);

        $postsDestacados = [];
        if (empty($busqueda) && empty($categoriaFiltro)) {
            $postsDestacados = $this->modelo->obtenerPostsDestacados();
        }

        $comentarios = [];
        if (!empty($posts)) {
            $comentarios = $this->modelo->obtenerComentarios($posts[0]['id']);
        }

        return [
            'posts' => $posts,
            'postsDestacados' => $postsDestacados,
            'comentarios' => $comentarios,
            'paginaActual' => $paginaActual,
            'totalPaginas' => $totalPaginas,
            'totalPosts' => $totalPosts,
            'busqueda' => $busqueda,
            'categoriaFiltro' => $categoriaFiltro,
            'ordenFiltro' => $ordenFiltro
        ];
    }
}

$controlador = new ControladorBlog();
$datosBlog = $controlador->obtenerDatosBlog();
extract($datosBlog);
?>
