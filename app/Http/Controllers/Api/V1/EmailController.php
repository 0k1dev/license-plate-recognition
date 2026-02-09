<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Mail\OtpMail;
use App\Mail\PhoneRequestApprovedMail;
use App\Mail\PropertyApprovedMail;
use App\Mail\PropertyRejectedMail;
use App\Models\OwnerPhoneRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    /**
     * Send email (support async queue)
     */
    protected function sendMail(string $to, $mailable, bool $queue = false)
    {
        if ($queue) {
            SendEmailJob::dispatch($to, $mailable);
        } else {
            Mail::to($to)->send($mailable);
        }
    }
    /**
     * Gửi OTP email
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
            'expires_in' => 'sometimes|integer|min:1|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        try {
            Mail::to($user->email)->send(
                new OtpMail($user, $request->otp, $request->expires_in ?? 5)
            );

            return response()->json([
                'success' => true,
                'message' => 'Email OTP đã được gửi thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Gửi email thông báo Property approved
     */
    public function sendPropertyApproved(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = Property::with('creator')->findOrFail($request->property_id);
        $user = $property->creator;

        if (!$user || !$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'User không có email.',
            ], 400);
        }

        try {
            Mail::to($user->email)->send(
                new PropertyApprovedMail($user, $property)
            );

            return response()->json([
                'success' => true,
                'message' => 'Email thông báo BĐS được duyệt đã gửi thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Gửi email thông báo Property rejected
     */
    public function sendPropertyRejected(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = Property::with('creator')->findOrFail($request->property_id);
        $user = $property->creator;

        if (!$user || !$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'User không có email.',
            ], 400);
        }

        try {
            Mail::to($user->email)->send(
                new PropertyRejectedMail($user, $property, $request->reason)
            );

            return response()->json([
                'success' => true,
                'message' => 'Email thông báo BĐS bị từ chối đã gửi thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Gửi email thông báo OwnerPhoneRequest approved
     */
    public function sendPhoneRequestApproved(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:owner_phone_requests,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $phoneRequest = OwnerPhoneRequest::with(['requester', 'property'])->findOrFail($request->request_id);
        $user = $phoneRequest->requester;

        if (!$user || !$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'User không có email.',
            ], 400);
        }

        try {
            Mail::to($user->email)->send(
                new PhoneRequestApprovedMail($user, $phoneRequest)
            );

            return response()->json([
                'success' => true,
                'message' => 'Email thông báo yêu cầu xem SĐT được duyệt đã gửi thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Gửi email tùy chỉnh (generic)
     */
    public function sendCustomEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            Mail::raw($request->message, function ($mail) use ($request) {
                $mail->to($request->to)
                    ->subject($request->subject);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email đã được gửi thành công.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
