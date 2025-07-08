<?php

namespace App\Http\Controllers;

use App\Models\Order;
use charlieuki\ReceiptPrinter\ReceiptPrinter;

class OrderPrintController extends Controller
{
    public function printStruk($orderId)
    {
        $order = Order::with(['customer', 'orderDetails.product', 'user'])->findOrFail($orderId);
        
        $mid = 'INV-' . $order->id;
        $store_name = 'MANDIRI
        KONVEKSI';
        $store_address = 'Jl. K.H. Abdul Wakid RT.15/RW.03, Banaran, Kerik, Magetan';
        $store_phone = '0821-4362-9650';
        $store_email = 'mandirikonveksi@gmail.com';
        $store_website = 'www.mandirikonveksi.com';
        $currency = 'Rp';
        $transaction_id = 'INV-' . $order->id;


        $printer = new ReceiptPrinter;
        $printer->init(
            config('receiptprinter.connector_type'),
            config('receiptprinter.connector_descriptor')
        );

        $printer->setStore($mid, $store_name, $store_address, $store_phone, $store_email, $store_website);
        $printer->setCurrency($currency);

        // Tambahkan produk
        foreach ($order->orderDetails as $detail) {
            $printer->addItem(
                $detail->product->nama_product ?? '-',
                $detail->quantity,
                $detail->harga
            );
        }

        $printer->calculateSubTotal();
        $printer->calculateGrandTotal();
 

        $printer->printReceipt();

        return back()->with('success', 'Struk berhasil dicetak!');
    }
} 