<?php

namespace App\Http\Controllers;

use App\c;
use Illuminate\Http\Request;
use App\User;
use App\Document;
use App\DocumentActions;
use App\DocumentFiles;
use Validator;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //$user_office = auth()->user()->office;
        //dd($user_office);
        $office = User::all();
        $doc_id = Document::all()->last();

        return view('admin.documents.create',compact('office','doc_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
         $offid = auth()->user()->uid;
         $officeid = User::select('id')
                        ->where('office','=', $request->act_office )->first();

        // dd($request->all());

         $office = auth()->user()->office;



         if ($office == 'Records Office'){
           $mytime = $request->date_received . ' ' . date('H:i:s');

                    $v = Validator::make($request->all(), [
                    'sender' => 'required',
                    'date_received' => 'required',   
                    'control_num' => 'required',         
                    ]);
        
                    if ($v->fails())
                        {   
                            return redirect()->back()->withErrors($v->errors())->withInput();
                        }
         }
         else{
            $mytime = date('Y-m-d H:i:s');
         }

                 
            if($offid == 2){
                $office_n = explode('-', $office)[1];
            }else{
                $office_n = auth()->user()->office;
            }

         
         if($office_n == 'Records Office'){
            $office = $request->sender;
         }else{
            $office = $office_n; //auth()->user()->office;
         }
         
        

         $maxFileSize = config('app.maxFileSize');

         
            $v = Validator::make($request->all(), [
                    'subject' => 'required',
                    'addressee' => 'required',
                    'requested' => 'required',
                    'actions' => 'required',
                    ]);
        
                    if ($v->fails())
                        {   
                            return redirect()->back()->withErrors($v->errors())->withInput();
                        }
        

        if($request->hasFile('scannedfile')){
                
                 $document = Document::create([
                'doc_type' => $request->doc_type,
                'doc_num' => $request->control_num,
                'dt_received' => $mytime,
                'actions' => $request->actions,
                'sender' => $office,
                'subject' => $request->subject,
                'act_office' => $request->act_office,
                'addressee' => $request->addressee,
                'encoder' => $office_n,
                'request' => $request->requested,
                'status' => 'On Process'  
                ]);

                           

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


                $document =  DocumentActions::create([
                    'document_id' => $document->id,
                    'office_id' => $officeid->id,
                    'status' => 'On Process',
                    'doc_type' => $request->doc_type,
                    'act_office' => $request->act_office,
                    'encoder' => $office_n
                ]);


        }else{

                $document = Document::create([
                'doc_type' => $request->doc_type,
                'doc_num' => $request->control_num,
                'dt_received' => $mytime,
                'actions' => $request->actions,
                'sender' => $office,
                'subject' => $request->subject,
                'act_office' => $request->act_office,
                'addressee' => $request->addressee,
                'encoder' => $office_n,
                'request' => $request->requested, 
                'status' => 'On Process'     
                    ]);

                    DocumentActions::create([
                        'document_id' => $document->id,
                        'office_id' => $officeid->id,
                        'status' => 'On Process',
                        'doc_type' => $request->doc_type,
                        'act_office' => $request->act_office,
                        'encoder' => $office_n
                    ]);
        }

        if(auth()->user()->office == 'Records Office'){
           // $doc_id = Document::all()->last();
            //$subject = $request->subject;
            //$sender = $office;
           // $addressee = $request->addressee;
            //$request = $request->requested;
            $document = Document::all()->last();
            //return view('admin.documents.printslip', compact('subject','doc_id','sender','addressee','request'));
            return view('admin.documents.printslip', compact('document'));
        }


        $request->session()->flash('msg','Your Document has been submitted to ' . $request->act_office);
        return redirect('user/outgoing');
    

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\c  $c
     * @return \Illuminate\Http\Response
     */


    public function show(c $c)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\c  $c
     * @return \Illuminate\Http\Response
     */
    public function edit(c $c)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\c  $c
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, c $c)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\c  $c
     * @return \Illuminate\Http\Response
     */
    public function destroy(c $c)
    {
        //
    }
}
