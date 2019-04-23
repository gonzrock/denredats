<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionsController extends Controller
{
    public function __construct(){

        $this->middleware('guest')->except('logout');
    }

    
     public function index(){
    	return view('user.sessions.index');
    }

    public function store(Request $request){
        
     
    	$rules = [
    		'username' => 'required',
    		'password' => 'required'
    	];

    	$request->validate($rules);

    	$data = request(['username','password']);
        
    	if ( ! auth()->attempt($data)) {
    		return back()->withErrors([
    			'message' => 'Wrong credentials please try again'
    		]);
    	}

       
    	return redirect('/user/incoming');//,compact('query'));

    }


     public function logout(){
    	
    	auth()->logout();

    	session()->flash('msg','You have been logged out successfully');

    	return redirect('/user/login');
    }
}
