<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;

class PostController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }

    public function index(){
        $posts = Post::all()->load('category');

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function show($id){
        $post = Post::find($id)->load('category');

        if (is_object($post)) {

            $data = array(
                'code' => 200,
                'status' => 'success',
                'posts' => $post
            );

        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La entrada no existe'
            );
        }
        return response()->json($data, $data['code']);

    }




}
