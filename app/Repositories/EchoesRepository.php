<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\Echoes;
use App\Models\EchoDefaultDescription;
use Yajra\DataTables\Facades\DataTables;
use Hash;
use Auth;
use Image;
use File;


class EchoesRepository
{


	public function getDatatable($request, $type)
	{
		
		$from = $request->from;
		$to = $request->to;
		$echo_default_description = EchoDefaultDescription::where('slug', $type)->first();
		$echoess = Echoes::where('echo_default_description_id', $echo_default_description->id)->whereBetween('date', [$from, $to])->orderBy('date', 'asc')->get();

		return Datatables::of($echoess)
			->addColumn('actions', function () {
				$button = '';
				return $button;
			})
			->make(true);
	}

	public function getEchoesPreview($id)
	{

		$no = 1;
		$total = 0;
		$total_discount = 0;
		$grand_total = 0;
		$echoes_detail = '';
		$tbody = '';

		$echoes = Echoes::find($id);

		$title = 'Echoes (INV'. date('Y', strtotime($echoes->date)) .'-'.str_pad($echoes->inv_number, 6, "0", STR_PAD_LEFT) .')';
		$total_riel = number_format($total*$echoes->rate, 0);
		$total_discount_riel = number_format($total_discount*$echoes->rate, 0);
		$grand_total_riel = number_format($grand_total*$echoes->rate, 0);

		
		$gtt = explode(".", number_format($grand_total,2));
		$gtt_dollars = $gtt[0];
		$gtt_cents = $gtt[1];

		$grand_total_in_word = Auth::user()->num2khtext($gtt_dollars, false) . 'ដុល្លារ' . (($gtt_cents>0)? ' និង'. Auth::user()->num2khtext($gtt_cents, false) .'សេន' : '');
		$grand_total_riel_in_word = Auth::user()->num2khtext(round($grand_total*$echoes->rate, 0), false);

		$echoes_detail = '<section class="echoes-print">
												<table class="table-header" width="100%">
													<tr>
														<td width="40%">
															<div class="KHOSMoulLight"style="color: red;">'. Auth::user()->setting()->sign_name_kh .'</div>
															<div style="color: blue; font-weight: bold; text-transform: uppercase; padding: 5px 0;">'. Auth::user()->setting()->sign_name_en .'</div>
															<div>'. Auth::user()->setting()->echo_description .'</div>
														</td>
														<td  width="20%">
															<img src="/images/setting/Logo.png" alt="IMG">
														</td>
														<td width="40%" class="text-center">
															<div>'. Auth::user()->setting()->address .'</div>
															<div style="padding: 5px 0;">Tel: '. Auth::user()->setting()->phone .'</div>
														</td>
													</tr>
												</table>
												<table class="table-information" width="100%" style="border-top: 4px solid red; margin: 10px 0 15px 0;">
													<tr>
														<td colspan="3">
															<h5 class="text-center KHOSMoulLight" style="padding: 20px 0 10px; color: blue;">'. $echoes->echo_default_description->name .'</h5>
														</td>
													</tr>
													<tr>
														<td>
															ឈ្មោះ/Name: <span class="pt_name">'. $echoes->pt_name .'</span>
														</td>
														<td>
															ភេទ/Gender: <span class="pt_gender">'. $echoes->pt_gender .'</span>
														</td>
														<td>
															អាយុ/Age: <span class="pt_age">'. $echoes->pt_age .'</span>
														</td>
													</tr>
												</table>
												<div class="echo_description">
													'. $echoes->description .'
												</div>
												<table class="table-detail" width="100%">
													<tr>
														<td width="70%" style="padding: 10px;">
															<img src="/images/echoes/'. $echoes->image .'" alt="IMG">
														</td>
														<td>
															<div>Le. '. date('d-m-Y', strtotime($echoes->date)) .'</div>
															<br/>
															<br/>
															<br/>
															<br/>
															<br/>
															<br/>
															<br/>
															<div>'. Auth::user()->setting()->sign_name_en .'</div>
														</td>
													</tr>
												</table>
												<div style="color: red; margin-top: 15px;" class="text-center">សូមកាន់លទ្ធផលនេះមកជាមួយផង ពេលពិនិត្យលើកក្រោយ អរគុណ។</div>
												<br/>
											</section>';

		return response()->json(['echoes_detail' => $echoes_detail, 'title' => $title]);
		// return $echoes_detail;

	}

	public function create($request, $path, $type)
	{
		$echo_default_description = EchoDefaultDescription::where('slug', $type)->first();
		// dd($echo_default_description->id);
		$echoes = Echoes::create([
			'date' => $request->date,
			'pt_no' => $request->pt_no,
			'pt_age' => $request->pt_age,
			'pt_name' => $request->pt_name,
			'pt_gender' => $request->pt_gender,
			'pt_phone' => $request->pt_phone,
			'description' => $request->description,
			'echo_default_description_id' => $echo_default_description->id,
			'created_by' => Auth::user()->id,
			'updated_by' => Auth::user()->id,
		]);
		
		if ($request->file('image')) {
			$image = $request->file('image');
			$echoes_image = time() .'_'. $echoes->id .'.png';
			$img = Image::make($image->getRealPath())->save($path.$echoes_image);
			$echoes->update(['image'=>$echoes_image]);
		}

		return $echoes;
	}

	public function update($request, $echoes, $path)
	{
		$echoes->update([
			'date' => $request->date,
			'pt_no' => $request->pt_no,
			'pt_age' => $request->pt_age,
			'pt_name' => $request->pt_name,
			'pt_gender' => $request->pt_gender,
			'pt_phone' => $request->pt_phone,
			'description' => $request->description,
			'updated_by' => Auth::user()->id,
		]);
		
		if ($request->file('image')) {
			$image = $request->file('image');
			$echoes_image = (($echoes->image!='default.png')? $echoes->image : time() .'_'. $echoes->id .'.png');
			$img = Image::make($image->getRealPath())->save($path.$echoes_image);
			$echoes->update(['image'=>$echoes_image]);
		}

		return $echoes;

	}

	public function destroy($request, $echoes, $path)
	{
    if (Hash::check($request->passwordDelete, Auth::user()->password)){
			
			$image = $echoes->image;
			if($echoes->delete()){

				if ($echoes->image!='default.png') {
					File::deleteDirectory($path.$image);
				}

				return $request->pt_name;
			}
    }else{
        return false;
    }
	}


}