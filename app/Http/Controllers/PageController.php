<?php

namespace App\Http\Controllers;
use App\Models\Slide;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\BillDetail;
use App\Models\Customer;
use App\Models\Cart;
use App\Models\Bill;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;



class PageController extends Controller
{
    public function getIndex(){
        $slide = Slide::all();
        $new_product = Product::where('new',1)->paginate(8);	
        $sanpham_khuyenmai = Product::where('promotion_price','<>',0)->paginate(8);	
        return view('page.trangchu',compact('slide','new_product','sanpham_khuyenmai'));
        
    }
    public function getLoaiSP($type){
        $sp_theoloai = Product::where('id_type',$type)->get();
        $sp_khac = Product::where('id_type','<>',$type)->paginate(3);
        $loai = ProductType::all();
        $l_sanpham = ProductType::where('id',$type)->get();
        return view('page.loai_sanpham',compact('sp_theoloai','sp_khac','loai','l_sanpham'));
    }

    public function getDetail(Request $req){
        $sanpham = Product::where('id', $req->id)->first();
        // $splienquan = Product::where('id','<>',$sanpham->id,'and','id_type','=',$sanpham->id_type,)->paginate(3);
        return view('page.chitiet_sanpham',compact('sanpham'));
    }

    public function getContact(){
        return view('page.lienhe');
    }

    public function getAbout(){
        return view('page.gioithieu');
    }

    //  Admin
    public function getIndexAdmin()
    {
        $products = Product::all();
        return view('pageadmin.admin')->with(['product' => $products, 'sumSold' => count(BillDetail::all())]);
    }

    public function getAdminAdd()
    {
        return view('pageadmin.formAdd');
    }
    public function postAdminAdd(Request $request)
    {
        $product = new product();
        if ($request->hasFile('inputImage')) {
            $file = $request->file('inputImage');
            $fileName = $file->getClientOriginalName('inputImage');
            $file->move('source/image/product', $fileName);
        }
        $file_name = null;
        if ($request->file('inputImage') != null) {
            $file_name = $request->file('inputImage')->getClientOriginalName();
        }

        $product->name = $request->inputName;
        $product->image = $file_name;
        $product->description = $request->inputDescription;
        $product->unit_price = $request->inputPrice;
        $product->promotion_price = $request->inputPromotionPrice;
        $product->unit = $request->inputUnit;
        $product->new = $request->inputNew;
        $product->id_type = $request->inputType;
        $product->save();
        return $this->getIndexAdmin();
    }

    public function getAdminEdit($id)
    {
        $product = Product::find($id);
        return view('pageadmin.formEdit')->with('product', $product);
    }

    public function postAdminEdit(Request $request)
    {
        $id = $request->editId;
        $product =  Product::find($id);
        if ($request->hasFile('editImage')) {
            $file = $request->file('editImage');
            $fileName = $file->getClientOriginalName('editImage');
            $file->move('source/image/product', $fileName);
        }
        if ($request->file('editImage') != null) {
            $product->image =$fileName;
        }

        $product->name = $request->editName;
        $product->description = $request->editDescription;
        $product->unit_price = $request->editPrice;
        $product->promotion_price = $request->editPromotionPrice;
        $product->unit = $request->editUnit;
        $product->new = $request->editNew;
        $product->id_type = $request->editType;
        $product->save();
        return $this->getIndexAdmin();
    }
    public function postAdminDelete($id)
    {
      $product = Product::find($id);
      $product->delete();
      return $this->getIndexAdmin();  
    }

    // Cart			
    // Không cần đăng nhập vẫn mua hàng được 
    					
    // public function getAddToCart(Request $req, $id){					
    //     $product = Product::find($id);					
    //     $oldCart = Session('cart')?Session::get('cart'):null;					
    //     $cart = new Cart($oldCart);					
    //     $cart->add($product,$id);					
    //     $req->session()->put('cart', $cart);					
    //     return redirect()->back();					
    // }	
    
    // Bắt buộc đăng nhập mới mua hàng
    public function getAddToCart(Request $req, $id)
    {
        if (Session::has('user')) {
            if (Product::find($id)) {
                $product = Product::find($id);
                $oldCart = Session('cart') ? Session::get('cart') : null;
                $cart = new Cart($oldCart);
                $cart->add($product, $id);
                $req->session()->put('cart', $cart);
                return redirect()->back();
            } else {
                return '<script>alert("Không tìm thấy sản phẩm này.");window.location.assign("/");</script>';
            }
        } else {
            return '<script>alert("Vui lòng đăng nhập để sử dụng chức năng này.");window.location.assign("/login");</script>';
        }
    }
      
    public function getDelItemCart($id){
        $oldCart = Session::has('cart')?Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->removeItem($id);
        if(count($cart->items)>0){
        Session::put('cart',$cart);

        }
        else{
            Session::forget('cart');
        }
        return redirect()->back();
    }		
    //----------------------------CHECKOUT-----------------------//
    
    public function getCheckout()
    {
      if(Session::has('cart')){
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        return view('page.checkout')->with(['cart' => Session::get('cart'),
                                                        'product_cart'=>$cart->items,
                                                        'totalPrice'=> $cart->totalPrice,
                                                        'totalQty'=>$cart->totalQty]);
      }  else{
        return redirect('page');
      }
    }

	public function postCheckout(Request $req){
		$cart = Session::get('cart');
		$customer = new Customer;
		$customer->name = $req->full_name;
		$customer->gender = $req->gender;
		$customer->email = $req->email;
		$customer->address = $req->address;
		$customer->phone_number = $req->phone;

		if(isset($req->notes)){
			$customer->note = $req->notes;
		} else{
			$customer->note = "Không có ghi chú gì";
		}

		$customer->save();

		$bill = new Bill;
		$bill->id_customer = $customer->id;
		$bill->date_order = date('Y-m-d');
		$bill->total = $cart->totalPrice;
		$bill->payment = $req->payment_method;
		if(isset($req->notes)){
			$bill->note = $req->notes;
		}else{
			$bill->note = "Không có ghi chú gì";
		}
		$bill->save();

		foreach($cart->items as $key =>$value){
			$bill_detail = new BillDetail;
			$bill_detail->id_bill = $bill->id;
			$bill_detail->id_product = $key;
			$bill_detail->quantity = $value['qty'];
			$bill_detail->unit_price = $value['price'] / $value['qty'];
		}

		Session::forget('cart');
		$wishlists = Wishlist::where('id_user', Session::get('user')->id)->get();
		if(isset($wishlists)){
			foreach($wishlists as $element){
				$element->delete();
			}
		}

	}
    
}
