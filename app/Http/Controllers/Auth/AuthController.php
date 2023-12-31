<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\View;




class AuthController extends Controller
{


    // Metodos para loguear
    public function login() {
        return view('auth.login');
    }
    public function authenticate(Request $request) {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            View::share('user', $user);
            return redirect()->route('dashboard');
        }



        return back()->withErrors(['invalid_credentials' => 'Email y contraseña son incorrecta'])->withInput();

    }


    // Metodos para registrar un usuario
    public function register() {
        return view('auth.register');
    }
    public function store(Request $request) {

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'age' => 'required|numeric|between:15,80',
            'gander' => 'required|in:M,F',
            'type_document' => 'required|in:TI,CC,CE',
            'document_number' => 'required|numeric',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password'
        ]);

        $user = new User;

        $user->first_name = $request->post('first_name');
        $user->last_name = $request->post('last_name');
        $user->age = $request->post('age');
        $user->gander = $request->post('gander');
        $user->type_document = $request->post('type_document');
        $user->document_number = $request->post('document_number');
        $user->email = $request->post('email');
        $user->password = $request->post('password');
        $user->status = 'ACTIVE';

        $user->save();

        session([
            'email' => $user->email,
            'password' => $request->post('password')
        ]);

        return redirect()->route('login')->with('success', 'Se ha registrado el usuario correctamente');

    }


    // Metodos para cambiar la contraseña
    public function forgotPassword() {
        return view('auth.verifyEmail');
    }
    public function verifyEmail(Request $request) {
        $request->validate([
            'email' => 'required|email'
        ]);
        $token = Str::random(60);

        DB::table('password_reset_tokens')->insert(
            ['email' => $request->email, 'token' => $token, 'created_at' => Carbon::now()]
        );

        Mail::send('auth.verify',['token' => $token], function($message) use ($request) {
                $message->to($request->email)->subject('Restablecer contraseña para la plataforma Biometric');
            });

        return back()->with('message', 'Se ha enviado un correo para restablecer su contraseña!');
    }
    public function resetPassword($token) {
        return view('auth.resetPassword', ['token' => $token]);
    }
    public function updatePassword(Request $request){
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required|min:5',
            'confirmPassword' => 'required|same:password'
        ]);

        $updatePassword = DB::table('password_reset_tokens')
        ->where(['email' => $request->email, 'token' => $request->token])
        ->first();

        if($updatePassword){

            $user = User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

            DB::table('password_reset_tokens')->where(['email'=> $request->email])->delete();

            return redirect()->route('login')->with('success', 'Contraseña actualizada correctamente');

        }else {
            return back()->withInput()->with('error', 'Token no existe o Token expirado!');
        }

    }


    // Metodo para cerrar sesion
    public function logout() {
        Auth::logout();
        Session::flush();
        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente');
    }

}
