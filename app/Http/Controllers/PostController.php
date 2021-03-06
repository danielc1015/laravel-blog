<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => [
            'index', 
            'show', 
            'getImage', 
            'getPostsByCategory',
            'getPostsByUser'
            ]]);
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


    public function store(Request $request){
        //recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //conseguir el usuario identificado
            $user = $this->getIdentity($request);

        //validar los datos
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
                'image' => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Faltan datos'
                );
            } else {
                //guardar el articulo
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;

                $post->save();

                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                );
            }
        
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Envia los datos correctamente'
            );
        }
        //devolver la respuesta
        return response()->json($data, $data['code']);
    }


    public function update($id, Request $request){
        //conseguir el usuario identificado
        $user = $this->getIdentity($request);

        // recoger datos por put
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //validar los datos
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);

            if ($validate->fails()) {
                $data = array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => $validate->errors(),

                );
            } else {
                //eliminar lo que no queremos actualizar
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['created_at']);
                unset($params_array['user']);

                //actualiar el registro
                $where = [
                    'id'=> $id,
                    'user_id'=> $user->sub,
                ];

                try {
                    $post = Post::updateOrCreate($where, $params_array);

                    $data = array(
                        'code' => 200,
                        'status' => 'success',
                        'post' => $post,
                        'postchanges' => $params_array
                    );
                } catch (\Throwable $th) {
                    $data = array(
                        'code' => 404,
                        'status' => 'error',
                        'message' => 'No se ha podido actualizar el Post. Post no encontrado'
                    );
                }
                
                
            }
            

        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Envia los datos correctamente'
            );
        }

        //devolver respuesta
        return response()->json($data, $data['code']);
    }


    public function destroy($id, Request $request){
        //conseguir usuario identificado
        $user = $this->getIdentity($request);

        //conseguir el post
        $post = Post::where('id', $id)->where('user_id', $user->sub)->first();

        if (!empty($post)) {
            //borrarlo
            $post->delete();

            //devolver algo
            $data = array(
                'code' => 200,
                'status' => 'success',
                'post' => $post
            );
        }else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            );
        }

        return response()->json($data, $data['code']);
    }


    private function getIdentity(Request $request){
        //conseguir usuario identificado
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }


    public function upload(Request $request)
    {
        //recoger la imagen de la peticion
        $image = $request->file('file0');

        //validar la imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes::jpg,jpeg,png,gif'
        ]);

        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen'
            );
        } else {
            //guardar la imagen en un disco
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = array(
                'code' => 200,
                'status' => 'success',
                'imagen' => $image_name
            );
        }  

        //devolver datos
        return response()->json($data, $data['code']);
    }


    public function getImage($filename)
    {
        // comprobar si existe
        $isset = \Storage::disk('images')->exists($filename);
        if ($isset) {
            //conseguir la imagen
            $file = \Storage::disk('images')->get($filename);
            //devolver resultado
            return response($file, 200);
        }else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            );
            return response()->json($data, $data['code']);
        }
        
    }

    public function getPostsByCategory($id)
    {
        $posts = Post::where('category_id', $id)->get();
        $data = array(
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        );
        return response()->json($data, $data['code']);
    }


    public function getPostsByUser($id)
    {
        $posts = Post::where('user_id', $id)->get();
        $data = array(
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        );
        return response()->json($data, $data['code']);
    }



}
