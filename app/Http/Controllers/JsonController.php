<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\json;
use App\Models\JsonModel;
use App\Models\ProjectUser;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JsonController extends Controller
{

    /**
     * @return Application|Factory|View
     */
    public function index(): View|Factory|Application
    {
        return view('json');
    }

    /**
     * @param JsonModel $jsonModel
     * @return Application|Factory|View
     */
    public function json(JsonModel $jsonModel): View|Factory|Application
    {
        $decodedData = json_decode($jsonModel->data, true);

        return view('read_json', ['jsonModel' => $jsonModel, 'data' => $decodedData]);
    }

    /**
     * @param JsonModel $jsonModel
     * @return RedirectResponse
     */
    public function deleteJson(JsonModel $jsonModel): RedirectResponse
    {
        if ($jsonModel->delete()) {
            return redirect()->back()->with('success', 'Json deleted, ID: ' . $jsonModel->id);
        }

        return redirect()->back()->withErrors(['msg' => 'Something went wrong']);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function addJson(Request $request): RedirectResponse
    {
        $start = Carbon::now();
        $memory = memory_get_usage();

        $jsonData = $request->get('json');
        $userId = (int)$request->get('user');
        $token = (string)$request->get('token');
        $user = ProjectUser::find($userId);

        if ($user) {
            $userToken = $user->tokens()->whereToken($token)->first();
            if ($userToken && $userToken->expires_at > Carbon::now()) {
                $json = new JsonModel();
                $json->user_id = $userId;
                $json->data = $jsonData;
                if ($json->save()) {
                    $end = Carbon::now();

                    return redirect()->back()->with('success', 'Json added, ID: ' . $json->id . ' Time wasted: ' . $end->diffInMicroseconds($start) . ' microseconds Memory wasted: ' . memory_get_usage() - $memory . ' bytes');
                }
            } else {
                return redirect()->back()->withErrors(['msg' => 'Token has expired!']);
            }
        }

        return redirect()->back()->withErrors(['msg' => 'Something went wrong']);
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateJson(Request $request): RedirectResponse
    {
        $start = Carbon::now();
        $memory = memory_get_usage();

        $codeToExecute = $request->get('code');
        $userId = (int)$request->get('user');
        $token = (string)$request->get('token');
        $jsonId = (int)$request->get('jsonId');
        $user = ProjectUser::find($userId);
        $jsonModel = JsonModel::find($jsonId);

        if (!$jsonModel) {
            return redirect()->back()->withErrors(['msg' => 'Json model doesnt exist']);
        }

        if ($user) {
            if ($jsonModel->user_id !== $user->id) {
                return redirect()->back()->withErrors(['msg' => 'You are not owner of the file!']);
            }
            $userToken = $user->tokens()->whereToken($token)->first();
            if ($userToken && $userToken->expires_at > Carbon::now()) {
                $data = json_decode($jsonModel->data);
                eval("$codeToExecute;");
                $jsonModel->data = json_encode($data);
                $jsonModel->save();
                $end = Carbon::now();

                return redirect()->back()->with('success', 'Json updated, ID: ' . $jsonModel->id . ' Time wasted: ' . $end->diffInMicroseconds($start) . ' microseconds Memory wasted: ' . memory_get_usage() - $memory . ' bytes');
            }

            return redirect()->back()->withErrors(['msg' => 'Token has expired!']);
        }

        return redirect()->back()->withErrors(['msg' => 'Something went wrong']);
    }
}
