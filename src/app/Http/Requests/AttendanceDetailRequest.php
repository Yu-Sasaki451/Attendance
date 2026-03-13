<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array{
    return [
        'in_at' => ['nullable', 'date_format:H:i'],
        'out_at' => ['nullable', 'date_format:H:i'],
        'break_in_at' => ['array'],
        'break_in_at.*' => ['nullable', 'date_format:H:i'],
        'break_out_at' => ['array'],
        'break_out_at.*' => ['nullable', 'date_format:H:i'],
        'note' => ['required'],
    ];
    }


    public function messages(): array{
    return [
        'in_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
        'out_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
        'break_in_at.*.date_format' => '休憩時間が不適切な値です',
        'break_out_at.*.date_format' => '休憩時間もしくは退勤時間が不適切な値です',
        'note.required' => '備考を記入してください'
    ];
    }


    public function withValidator($validator): void{
    $validator->after(function ($validator) {
        $inAt = $this->input('in_at');
        $outAt = $this->input('out_at');

        if ($inAt && $outAt && $inAt >= $outAt) {
            $validator->errors()->add('attendance_time', '出勤時間もしくは退勤時間が不適切な値です');
        }

        $breakInTimes = $this->input('break_in_at', []);
        $breakOutTimes = $this->input('break_out_at', []);

        foreach ($breakInTimes as $index => $breakInAt) {
            $breakOutAt = $breakOutTimes[$index] ?? null;

            if ($breakInAt && $inAt && $breakInAt < $inAt) {
                $validator->errors()->add("break_time.$index", '休憩時間が不適切な値です');
            }

            if ($breakInAt && $outAt && $breakInAt > $outAt) {
                $validator->errors()->add("break_time.$index", '休憩時間が不適切な値です');
            }

            if ($breakOutAt && $outAt && $breakOutAt > $outAt) {
                $validator->errors()->add("break_time.$index", '休憩時間もしくは退勤時間が不適切な値です');
            }
        }
    });
}
}
