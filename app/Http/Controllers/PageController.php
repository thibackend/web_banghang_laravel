<?php

namespace App\Http\Controllers;
use App\Models\Slide;
use App\Models\Product;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function getIndex(){
        $slide = Slide::all();
        $new_product = Product::where('new',1)->paginate(4);	
        $sanpham_khuyenmai = Product::where('promotion_price','<>',0)->paginate(4);	
        return view('page.trangchu',compact('slide','new_product','sanpham_khuyenmai'));
        
    }
    public function getLoaiSP($type){
        $sp_theoloai = Product::where('id_type',$type)->get();
        return view('page.loai_sanpham',compact('sp_theoloai'));
    }

    public function getDetail(){
        return view('page.chitiet_sanpham');
        $sanpham = Product::where('id', $request->id)->first();
        $splienquan = Product::where('id','<>',$sanpham->id,'and','id_type','=',$sanpham->id_type,)->paginate(3);
        return view('detailproduct',compact('sanpham','splienquan'));
    }

    public function getContact(){
        return view('page.lienhe');
    }

    public function getAbout(){
        return view('page.gioithieu');
    }
    
}
