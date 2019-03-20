<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
                'chenges' => $params_array
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


    
} 