<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\User;
use App\Document;
use App\DocumentActions;
use App\DocumentFiles;
use App\DocumentTravel;
use Validator;
use DB;
use Carbon;

class UsersController extends Controller
{
   public function edited($id, $office_id, $office){

        $offid = auth()->user()->uid;
        $document = Document::find($id);
        $offices = User::all()->where('office','!=', $office);
                              //->where('uid','=', 1);
        //dd($office_id);

        $remarks = DocumentActions::select('remarks','created_at')
                                    ->where('office_id','=', $office_id )
                                    ->where('document_id','=', $id )
                                    ->orderBy('created_at', 'desc')->first();
        
        $office_main = $office;
        $attachments = DocumentFiles::all();

        $travels = DocumentTravel::select('name','destination','departure','arrival','departure')
                                    ->where('document_id','=', $id)->first();
                       
        //dd($offid);

        return view('user.action',compact('document','office_id','offices','office_main','remarks','attachments','travels','offid'));
    }

    public function updated(Request $request, $id){
        
         $user_office = auth()->user()->office;
         $office_id = \Request::get('offid');
         $doc_type = \Request::get('doctype');
         $classi = \Request::get('classifications');
       

         $document = Document::find($id);
        

         if($request->act_off == null && $request->act_office1 == null && $request->status != 'End Transaction'){

            $request->session()->flash('msg','Error.. Please select where to route..Thank You!');
            return redirect('user/incoming');

            }


         if($request->act_off == null){
            $office1 = $request->act_office1;
         }else{
            $office1 = $request->act_off;
         }

         //dd($u_id);


         if ($user_office == 'Office of the RED'){
            $classification = $request->classification;

         }else {
            $classification = $classi;
         }
   
         if($office1 != null){
            $office_id = explode(' - ', $office1)[0];
            $office = explode(' - ', $office1)[1];
         }else{
           $office = $request->offmain;
         }
    
         if($request->act_office1 != null){
            $o_id = explode('-', $office_id)[1];
            $office_id = explode('-', $office1)[0];
            $office = $o_id . "-" . $office;
            $div_encode = $request->encoder;
           
        }else{
            $o_id = null;
            $div_encode = $request->offmain;
        }

        //dd($office_id);



        if($request->hasFile('scannedfile')){

         
                foreach ($request->scannedfile as $image) {
                            
                            $filename = $image->getClientOriginalName();

                                       DocumentFiles::create([
                                            'document_id' => $document->id,
                                            'filename' => $filename
                                             ]); 
                                         }   

                        foreach($request->file('scannedfile') as $file)
                            {
                                $name=$file->getClientOriginalName();
                                $file->move(public_path().'/uploads/', $name);  
                                $data[] = $name;  
                            }
                   

         } 


         $document->update([
            'status' => $request->status,
            'act_office' => $office,
            'encoder'   => $div_encode,
            'classification' => $classification
         ]);

        DocumentActions::create([
            'document_id' => $document->id,
            'office_id' => $office_id,
            'doc_type' =>  $doc_type, 
            'encoder' => $div_encode,
            'act_office' => $office,
            'status' => $request->status,
            'remarks' => $request->remarks
         ]);

         $request->session()->flash('success','Success..');

         return redirect('/user/incoming');

    }


public function created(Request $request){
        
        $user_office = auth()->user()->office;
        
    $doc_type = $request->doc_type; 
    //dd($request->doc_type);
    if ($request->doc_type == 'Travel Order'){
        $office = User::select('office','uid')
                         ->where('office','!=', $user_office )->get();
        $doc_id = Document::all()->last();
          
        return view('user.travel_order',compact('office','doc_id','doc_type'));
    }else {

        $office = User::select('office','uid')
                         ->where('office','!=', $user_office )->get();
                         
        $doc_id = Document::all()->last();

        return view('admin.documents.create',compact('office','doc_id','doc_type','user_office'));
    }
    
}

public function livesearch(){
    if (Auth::check()){
        return view('user.live_search');
    }else{
         return redirect('/user/login');
    }
}


public function search(Request $request)
    {
        
        if($request->ajax())
        {   
            $output="";
            
        
            $documents = DB::table('documents')
                            //->join('document_travels', 'documents.id', '=', 'document_travels.document_id')
                            ->where('subject','LIKE','%'.$request->search.'%')->get();
                     
            
            if ($documents){
               
                foreach ($documents as $key => $document) {
                    $output.='<tr>'.
                         '<td>'.$document->doc_num.'</td>'.
                         '<td>'.$document->subject.'</td>'.
                         '<td>'.$document->created_at.'</td>'.
                         '<td>'.$document->status.'</td>'.
                         '<td><a href ="/user/trackdocument/'.$document->id.'"><button type="button" class="btn btn-danger btn-xs">Track</button></a></td>'.
                         '</tr>';
                }
               
                return Response($output);
            }
         }
 
    }

//============================
public function tosearch(Request $request)
    {
        
        if($request->ajax())
        {   
            $output="";
                   
            $documents = DB::table('document_travels')
                            ->where('name','LIKE','%'.$request->search.'%')
                            ->orderBy('created_at', 'desc')->get();
                     
            //dd($documents->subject);
            if ($documents){
               
                foreach ($documents as $key => $document) {
                    $output.='<tr>'.
                         '<td>DOC2019-'.$document->document_id.'</td>'.
                         '<td>'.$document->name.'</td>'.
                         '<td>'.$document->destination.'</td>'.
                         '<td>'.$document->departure.'</td>'.
                         '<td>'.$document->arrival.'</td>'.
                         '<td><a href ="/user/trackdocument/'.$document->document_id.'"><button type="button" class="btn btn-danger btn-xs">Track</button></a></td>'.
                         '</tr>';
                }
               
                return Response($output);
            }
         }
 
    }


//==============

public function numsearch(Request $request)
    {
        
        if($request->ajax())
        {   
            $output="";
                   
            $documents = DB::table('documents')
                            ->where('doc_num','LIKE','%'.$request->search.'%')
                            ->orderBy('created_at', 'desc')->get();
                     
            //dd($documents->subject);
            if ($documents){
               
                foreach ($documents as $key => $document) {
                    $output.='<tr>'.
                         '<td>'.$document->doc_num.'</td>'.
                         '<td>'.$document->subject.'</td>'.
                         '<td>'.$document->created_at.'</td>'.
                         '<td>'.$document->status.'</td>'.
                         '<td><a href ="/user/trackdocument/'.$document->id.'"><button type="button" class="btn btn-danger btn-xs">Track</button></a></td>'.
                         '</tr>';
                }
               
                return Response($output);
            }
         }
 
    }


   //============================ 

public function trackdoc($id){
        
        $user_office = auth()->user()->office;       
        $query = DB::table('documents')
                    ->join('document_actions', 'documents.id', '=', 'document_actions.document_id')
                    ->join('users', 'document_actions.office_id', '=', 'users.id')
                    ->select('documents.id','documents.subject','document_actions.status','document_actions.remarks','document_actions.created_at','document_actions.encoder' ,'users.office')
                    ->where('documents.id','=', $id )
                    ->orderBy('document_actions.created_at', 'DESC')
                    ->get();

        $attachments = DocumentFiles::all();
      //dd($query);
    if($query->count()){
        $count = $query->count();
        //dd($count);
       return view('user.trackdoc',compact('query','doc_id','count','user_office','attachments'));
    }else{
       session()->flash('msg','No Records Found.');
       return view('user.live_search'); 
    }
        

    } 

public function search_type(Request $request){

    //dd($request->all());
    if (Auth::check()){
        if ($request->searchdoc_type == 'Travel Order'){
            return view('user.tolive_search');
        }else if($request->searchdoc_type == 'ID'){
            return view('user.numlive_search');
        }else {
            return view('user.live_search');
        }
    }else{
             return redirect('/user/login');
    }
}



}
