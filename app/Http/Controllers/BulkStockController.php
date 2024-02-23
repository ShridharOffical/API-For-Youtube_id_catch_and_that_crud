<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\customclasses\Corefunctions;
use App\Models\User;
use App\Models\Stock;
use App\Models\Docket;
use App\Models\Auth;
use DB;
use Illuminate\Support\Facades\File;
use Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use App\customclasses\AdditionalFunctions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class BulkStockController extends Controller
{
    public function __construct()
    {
    }


    public function addStockBulk(Request $request)
    {
        try {

            if (!isset($request['stock_file']))
                throw new Exception(config('constants.VALIDATIONS.REQUIRED_FIELD'), 404);

            $stock_file = $request->file('stock_file');

            $existing_vin_numbers = [];
            $existing_mddp_numbers = [];
            $is_mddp_blank = false;
            $is_main_loc_id_missing = false;

            if ($stock_file) {
                $spreadsheet = IOFactory::load($stock_file);
                $worksheet = $spreadsheet->getActiveSheet();
                $excelData = $worksheet->toArray();
                if (!empty($excelData) && count($excelData) > 1) {

                    array_shift($excelData);
                }
                foreach ($excelData as $row) {
                    $stockData1 = [];

                    if (!$row[1]) {
                        $is_mddp_blank = true;
                        continue;
                    } else {
                        $stockData1 = Stock::checkStockByMddpNo($row[1]);
                    }

                    if (!empty($stockData1)) {
                        $existing_mddp_numbers[] = $stockData1['mddp_no'];
                    }

                    $stockData2 = [];
                    if (!$row[3]) {
                        $row[3] = "";
                    } else {
                        $stockData2 = Stock::checkStockByVinNo($row[2]);

                        if (!empty($stockData2)) {
                            $existing_vin_numbers[] = $stockData2['vin_no'];
                        }
                    }

                    if (!$row[23]) {
                        $is_main_loc_id_missing = true;
                        continue;
                    }

                    if (empty($stockData1) && empty($stockData2)) {
                        DB::table('stocks')->insertGetId(
                            array(
                                'mddp_no' => $row[1],       // INDEX 1 IS USED FOR CONDITIONS
                                'mddp_date' => $row[2],
                                'vin_no' => $row[3],        // INDEX 3 IS USED FOR CONDITIONS
                                'sc_no' => $row[4],
                                'km_inv_date' => $row[5],
                                'age' => $row[6],
                                'suffix' => $row[7],
                                'model' => $row[8],
                                'grade' => $row[9],
                                'ext_color' => $row[10],
                                'int_color' => $row[11],
                                'suffix_old_new' => $row[12],
                                'year' => $row[13],
                                'p_t_m' => $row[14],
                                'location' => $row[15],
                                'status' => $row[16],
                                'status_date' => $row[17],
                                'customer_name' => $row[18],
                                'so_name' => $row[19],
                                'tl' => $row[20],
                                'team' => $row[21],
                                'eng_no' => $row[22],
                                'created_by_main_loc_id' => $row[23],   // Main loc Id   // INDEX 23 IS USED FOR CONDITIONS
                                'created_at' => Carbon\Carbon::now(),
                                'updated_at' => Carbon\Carbon::now()
                            )
                        );
                    }
                }
            }

            $response['code'] = config('constants.API_CODES.SUCCESS');
            $response['data']['existing_vin_nos'] = $existing_vin_numbers;
            $response['data']['existing_mddp_nos'] = $existing_mddp_numbers;
            $response['data']['is_mddp_blank'] = $is_mddp_blank;
            $response['data']['is_main_loc_id_missing'] = $is_main_loc_id_missing;
            $response['status'] = config('constants.API_CODES.SUCCESS_STATUS');
            $response['message'] = config('constants.VALIDATIONS.STOCK_SUCCESS');
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => $e->getCode(), 'status' => config('constants.API_CODES.ERROR_STATUS')], config('constants.API_CODES.ERROR'));
        }
    }


    public function addDocketBulk(Request $request)
    {
        try {
            if (!isset($request['docket_file']))
                throw new Exception(config('constants.VALIDATIONS.REQUIRED_FIELD'), 404);

            $docket_file = $request->file('docket_file');

            if ($docket_file) {
                $spreadsheet = IOFactory::load($docket_file);
                $worksheet = $spreadsheet->getActiveSheet();
                $excelData = $worksheet->toArray();
                if (!empty($excelData) && count($excelData) > 1) {
                    array_shift($excelData);
                } else {
                    throw new Exception(config('constants.VALIDATIONS.INVALID_SHEET'), 404);
                }

                $rowsNotInserted = [];

                foreach ($excelData as $key => $row) {

                    //validate if any cell in the current row is empty or null
                    $isAllNonNull = true;
                    for ($i = 1; $i <= 21; $i++) {
                        if ($row[$i] === null) {
                            $isAllNonNull = false;
                            break;
                        }
                    }

                    // skip adding current row to database
                    if (!$isAllNonNull) {
                        $rowsNotInserted[] = $key + 1;
                        continue;
                    }

                    // Check if given user exists
                    $userExists = User::where('id', $row[21])->first();

                    // skip adding current row to database
                    if (!$userExists) {
                        $rowsNotInserted[] = $key + 1;
                        continue;
                    }

                    $dmax = Docket::max('dnum');

                    $varDckNo = $dmax + 1;
                    $dnum = $dmax + 1;
                    if (strlen($varDckNo) === 1) {
                        $varDckNo = '000' . $varDckNo;
                    } elseif (strlen($varDckNo) === 2) {
                        $varDckNo = '00' . $varDckNo;
                    } elseif (strlen($varDckNo) === 3) {
                        $varDckNo = '0' . $varDckNo;
                    } else {
                        $varDckNo = '' . $varDckNo;
                    }

                    $dmax = $dmax + 1;

                    $varDckNo = $row[3] . "/" . date('y') . "/" . date('m') . "/" . $dmax;

                    $docketid = DB::table('docket_details')->insertGetId(
                        array(
                            'docket_no' => $varDckNo,
                            'booking_date' => $row[1],
                            'so_name' => $row[2],
                            'suffix' => $row[4],
                            'model' => $row[5],
                            'grade' => $row[6],
                            'int_color' => $row[7],
                            'color' => $row[8],
                            'mode_of_payment' => $row[9],
                            'insurance' => $row[10],
                            'registration' => $row[11],
                            'created_by' => $row[12],
                            'order_source' => $row[13],
                            'cost_of_vehicle' => $row[20],
                            'total_charges' => $row[20],
                            'created_by_user_id' => $row[21],
                            'dnum' => $dnum,
                            'created_at' => Carbon\Carbon::now()
                        )
                    );

                    $vehicleId = DB::table('vehicle_details')->insertGetId(
                        array(
                            'docket_id' => $docketid,
                            'suffix' => $row[4],
                            'model' => $row[5],
                            'grade' => $row[6],
                            'int_color' => $row[7],
                            'color' => $row[8],
                            'created_at' => Carbon\Carbon::now()
                        )
                    );

                    $custId = DB::table('customer_details')->insertGetId(
                        array(
                            'docket_id' => $docketid,
                            // 'customer_name' =>  $row[14],
                            'pan_no' => $row[18],
                            'dob' => $row[19],
                            'created_at' => Carbon\Carbon::now()
                        )
                    );

                    // $paymentId = DB::table('payment_details')->insertGetId(array(
                    //     'docket_id' =>  $docketid,
                    //     'created_at' => Carbon\Carbon::now()
                    // ));

                    $regAddrId = DB::table('address_details')->insertGetId(
                        array(
                            'docket_id' => $docketid,
                            'name' => $row[14],
                            'tel_mobile_no' => $row[15],
                            'address' => $row[16],
                            'mail_id' => $row[17],
                            'type' => 'registraion',
                            'created_at' => Carbon\Carbon::now()
                        )
                    );

                    $correspondanceAddrId = DB::table('address_details')->insertGetId(
                        array(
                            'docket_id' => $docketid,
                            'name' => $row[14],
                            'tel_mobile_no' => $row[15],
                            'address' => $row[16],
                            'mail_id' => $row[17],
                            'type' => 'correspondance',
                            'created_at' => Carbon\Carbon::now()
                        )
                    );
                }
            } else {
                throw new Exception(config('constants.VALIDATIONS.INVALID_SHEET'), 404);
            }

            if (!empty($rowsNotInserted)) {
                $data = ['skipped_rows' => $rowsNotInserted];
            } else {
                $data = [];
            }

            $response['code'] = config('constants.API_CODES.SUCCESS');
            $response['status'] = config('constants.API_CODES.SUCCESS_STATUS');
            $response['message'] = 'Docket created successfully';
            $response['data'] = $data;
            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => $e->getCode(), 'status' => config('constants.API_CODES.ERROR_STATUS')], config('constants.API_CODES.ERROR'));
        }
    }
}
