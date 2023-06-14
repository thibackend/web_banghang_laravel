<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    public $items = null; 
    public $totalQty = 0;
    public $totalPrice =0;

    public function __construct($oldCart)
    {
        if($oldCart){
            $this->items = $oldCart->items;
            $this->totalQty = $oldCart->totalQty;
           $this->totalPrice = $oldCart->totalPrice;
        }
    }
    // Thêm phần tử vào giỏ hàng 
    public function add($item, $id, $qty = 1)
    {
        if($item->promotion_price == 0){
            $giohang = [
                'qty' => 0,
                'price' => $item->unit_price,
                'item' => $item
            ];
            if($this->items){
                if(array_key_exists($id, $this->items)){
                    $giohang = $this->items[$id];
                }
            }
            $giohang['qty'] = $giohang['qty'] + $qty;
            $giohang['price'] = $item->unit_price * $giohang['qty'];
            $this->items[$id] = $giohang;
            $this->totalQty = $this->totalQty + $qty;
            $this->totalPrice += $item->unit_price * $giohang['qty'];
        } else {
            $giohang = ['qty' => 0, 'price' => $item->promotion_price, 'item' => $item];
            if($this->items){
                if(array_key_exists($id, $this->items)){
                   $giohang = $this->items[$id];
                }
            }
            $giohang['qty'] = $giohang['qty'] + $qty;
            $giohang['price'] = $item->promotion_price * $giohang['qty'];
            $this->items[$id] = $giohang;
            $this->totalQty = $this->totalQty + $qty;
            $this->totalPrice += $item->promotion_price * $giohang['qty'];
        }
    }
     //xóa 1                                 
    public function reduceByOne($id)
    {
        $this->items[$id]['qty']--; // Giảm số lượng một đơn vị
        $this->items[$id]['price'] = $this->items[$id]['item']['price']; //* $this->items[$id]['qty']; // Cập nhật giá tiền dựa trên số lượng mới
        $this->totalQty--; // Giảm tổng số lượng một đơn vị
        $this->totalPrice -= $this->items[$id]['item']['price']; // Giảm tổng giá tiền bằng giá tiền của mặt hàng đã giảm

        if ($this->items[$id]['qty'] <= 0) {
            unset($this->items[$id]); // Xóa phần tử nếu số lượng <= 0
        }
    }
    // xóa nhiều
    public function removeItem($id)
    {
        $this->totalQty -= $this->items[$id]['qty']; // Giảm tổng số lượng bằng số lượng của mặt hàng sẽ bị xóa
        $this->totalPrice -= $this->items[$id]['price']; // Giảm tổng giá tiền bằng giá tiền của mặt hàng sẽ bị xóa
        unset($this->items[$id]); // Xóa phần tử
    }

}
