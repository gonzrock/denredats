<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Document;
use App\DocumentFiles;
use App\DocumentActions;
use App\DocumentTravel;
use DB;
use Validator;
use App\User;
use Hash;




class HomeController extends Controller
{
    public function index(){
    	return view('admin.documents.dashboard');
    }

    public function incoming(){

        $count = 0;

        if (Auth::check()){
        $office_name = auth()->user()->office;
        $office_id = auth()->user()->id;
        $offid = auth()->user()->uid;

        if ($offid == 2){
            $office = explode('-', $office_name)[1];
        }else{
            $office = $office_name;
        }

        //dd($office);

                 $users = Document::orderBy('updated_at', 'DESC')
                                          ->where('status','!=','End Transaction')
                                          ->where('act_office','=', $office)
                                          ->orWhere('act_office', 'LIKE', $office_id.'%') //->get();
                                          ->paginate(10); 
                                            

                //dd($users);        

                $incom = Document::orderBy('status', 'DESC')
                                          ->where('status','!=','End Transaction')
                                          ->where('act_office','=', $office)->get();



                /*$totalincoming = DocumentActions::orderBy('updated_at', 'DESC')
                                          //->where('status','!=','End Transaction')
                                          ->where('act_office','=', $office)->get();*/

                $totalincoming = DB::table('document_actions')
                                    ->join('documents', 'document_actions.document_id', '=', 'documents.id')
                                    ->select('document_actions.document_id','document_actions.encoder','documents.id','documents.doc_num','documents.subject','document_actions.status','document_actions.doc_type','document_actions.created_at')
                                    ->where('document_actions.act_office','=', $office)
                                    ->orderBy('document_actions.created_at', 'DESC')
                                    ->paginate(5);


                $incount = $incom->count();
                session(['incoming' => $incount]);
                session(['totalincoming' => $totalincoming->total()]);

                $attachments = DocumentFiles::all();

                return view('admin.documents.incoming',compact('users','attachments','office_id','office','totalincoming'));
        
            }else{
            return redirect('/user/login');
            }

    }

    public function outgoing(){

    if (Auth::check()){
        $office_name = auth()->user()->office;
        $id = auth()->user()->id;
        $office_id = auth()->user()->id;
        $offid = auth()->user()->uid;


        if ($offid == 2){
            $office = explode('-', $office_name)[1];
        }else{
            $office = $office_name;
        }

        

        $query = DB::table('documents')
                    ->join('document_actions', 'documents.id', '=', 'document_actions.document_id')
                    ->select('documents.id','documents.doc_num','documents.subject','documents.status','document_actions.act_office','document_actions.created_at')
                    ->where('document_actions.encoder','=', $office )
                    ->Where('document_actions.office_id','!=', $id )
                    ->Where('document_actions.act_office', 'NOT LIKE', '%'.'-'.'%')
                    ->orderBy('document_actions.created_at', 'DESC')
                    ->paginate(10);
                    //->get();

        session(['outgoing' => $query->total()]);


    	return view('admin.documents.outgoing',compact('query','office_name'));

    }else{
            return redirect('/user/login');
            }

    }

     public function newdoc(){
    	return view('admin.documents.create');
    }


public function printslip($id){

    $document = Document::find($id);

    return view('admin.documents.printslip',compact('document'));
}

public function editsub($id){

    $document = Document::find($id);

    return view('admin.documents.editsub',compact('document'));
}



public function travel(Request $request){
        

        $officeid = User::select('id','name')
                         ->where('office','=', $request->act_office )->first();

        $office = auth()->user()->office;
             //dd($request->all());
            $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'position' => 'required',
                    'purpose' => 'required',
                    'destination' => 'required',
                    'departure' => 'required',
                    'arrival' => 'required',
                    ]);
        
            if ($validator->fails())
         {
             return redirect()->back()->withErrors($validator->errors())->withInput();
        }
        $mytime = date('Y-m-d H:i:s');
      
                $document = Document::create([
                'doc_type' => 'Travel Order',
                'dt_received' => $mytime,
                'actions' => 'information & record',
                'sender' => $office,
                'subject' => $request->purpose,
                'act_office' => $request->act_office,
                'addressee' => $officeid->name,
                'encoder' => $office,
                'request' => $request->requested,
                'status' => 'On Process'     
                    ]);

                    DocumentActions::create([
                        'document_id' => $document->id,
                        'office_id' => $officeid->id,
                        'doc_type' => 'Travel Order',
                        'act_office' => $request->act_office,
                        'status' => 'On Process',
                        'encoder' => $office
                    ]);

                    $document =  DocumentTravel::create([
                    'document_id' => $document->id,
                    'name' => $request->name,
                    'position' => $request->position,
                    'destination' => $request->destination,
                    'departure' => $request->departure,
                    'arrival' => $request->arrival,
                ]);
     
        $request->session()->flash('msg','Your Document has been submitted to ' . $request->act_office);
        return redirect('user/outgoing');
    
 

}

public function changepassword(){

        return view('user.changepassword');
    }

public function store(Request $request){

         $rules = [
            'password' => 'required',
            'newpassword' => 'required',
            'confirm' => 'required'
        ];

        $request->validate($rules);
        
        if (!(Hash::check($request->get('password'), Auth::user()->password))) {
            // The passwords matches
            return redirect()->back()->with("error","Your current password did not match with the password you provided. Please try again.");
        }

        if(strcmp($request->get('password'), $request->get('newpassword')) == 0){
            //Current password and new password are same
            return redirect()->back()->with("error","New Password cannot be same as your current password. Please choose a different password.");
        }

        if(strcmp($request->get('newpassword'), $request->get('confirm')) != 0){
            //Current password and new password are same
            return redirect()->back()->with("error","New Password did not match.");
        }
        
        $user = Auth::user();
        $user->password = bcrypt($request->get('newpassword'));
        $user->save();
 
        return redirect()->back()->with("success","Password changed successfully !");
       
    }

public function editprofile(){
    $userid = auth()->user()->id;
    
    $user = User::find($userid);
    
    return view('user.editprofile',compact('user'));
}

public function updateprofile(Request $request){
        $userid = auth()->user()->id;

            
        $request->validate([
            'name'  => 'required',
            'designation'  => 'required',
            'photo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

    if($request->hasFile('photo')){
        $user = Auth::user();
        $avatarName = $user->id.'_avatar'.time().'.'.request()->photo->getClientOriginalExtension();

        $request->photo->storeAs('avatars',$avatarName);

        $user->photo = $avatarName;
        $user->save(); 

        $request->photo->move(public_path().'/avatar/', $avatarName);
     }


        $user = User::findOrFail($userid);
        $user->name = $request->name;
        $user->designation = $request->designation;
        $user->save();

        return redirect()->back()->with("success","Update successfully !");
   


}

public function updatesubject(Request $request){
                 
        //dd($request->all());

        $document = Document::findOrFail($request->doc_id);
        $document->subject = $request->subject;
        //$user->designation = $request->designation;
        $document->save();

        //return redirect()->back()->with("success","Update successfully !");
        $request->session()->flash('msg','Subject has been updated');
        return redirect('user/outgoing');


}



}
