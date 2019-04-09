<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller
{
    public function pruebas(Request $request){
        return "Accion de pruebas de usercontroller";
    }

    public function register(Request $request){

        //recoger los datos del usuario por POST
        $json = $request->input('json', null);
        $params = json_decode($json); //objeto
        $params_array = json_decode($json, true); //array

        if (!empty($params) && !empty($params_array)) {
            //limpiar datos
            $params_array = array_map('trim', $params_array);

            //Validar datos
            $validate = \Validator::make($params_array, [
                'name'       => 'required|alpha',
                'surname'    => 'required|alpha',
                'email'      => 'required|email|unique:users',
                'password'   => 'required'
            ]);
            
            if ($validate->fails()) {
                //la validacion ha fallado
                $data = array(
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'El usuario no se ha creado',
                    'errors'  => $validate->errors()
                );

            }else {
                //validacion pasada correctamente 
                

                //cifrar contraseña
                $pwd = hash('SHA256', $params->password);

                //comprobar si el usuario exista ya (duplicado)

                //Crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->name = $params_array['name'];
                $user->password = $pwd;
                $user->role = 'role_user';

                $user->save();
                
                
                $data = array(
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => 'El usuario se ha creado cprrectasmente',
                    'user'    => $user
                );
            }

        }else {
            $data = array(
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Los datos enviadps no son correctos'
            );
        }
        

        return response()->json($data, $data['code']);
    }


    public function login(Request $request){
        
        $jwtAuth = new \JwtAuth();

        //Recibir datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
       
        //Validar datos
        $validate = \Validator::make($params_array, [
            'email'      => 'required|email',
            'password'   => 'required'
        ]);
        
        if ($validate->fails()) {
            //la validacion ha fallado
            $signup = array(
                'status'  => 'error',
                'code'    => 404,
                'message' => 'El usuario no se ha podido loguear',
                'errors'  => $validate->errors()
            );

        }else {

            //Cifrar la contraseña
            $pwd = hash('SHA256', $params->password);
            //Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);
            if (!empty($params->gettoken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }

        }

        return response()->json($signup, 200);
    }

    public function update(Request $request){
        //comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        //recoger datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if($checkToken && !empty($params_array)){ //actualizar usuario

            //sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            //validar datos
            $validate = \Validator::make($params_array, [
                'name'       => 'required|alpha',
                'surname'    => 'required|alpha',
                'email'      => 'required|email|unique:users,'.$user->sub
            ]);

            //quitar los campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //actualizar usuario en ddbb
            $user_update = User::where('id', $user->sub)->update($params_array);

            //devolver array con el resultado
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array
            );


        }else{
            //devolver error
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );
        }
        return response()->json($data, $data['code']);

    }


    public function upload(Request $request){

        // Recoger datos de la peticion
        $image = $request->file('file0');

        //Validacion de images
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        
        // Guardar imagen
        if (!$image || $validate->fails()) {

            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen'
            );

        }else {

            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name,
                'message' => 'Imagen subida correctamente'
            );
   
        }

        return response()->json($data, $data['code']);
    }


    public function getImage($filename){
        $isset = \Storage::disk('users')->exists($filename);
        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            );
            return response()->json($data, $data['code']);
        }
        
    }

    public function detail($id){
        $user = User::find($id);

        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El usuario no existe'
            );
        }

        return response()->json($data, $data['code']);
    }


    
} 
