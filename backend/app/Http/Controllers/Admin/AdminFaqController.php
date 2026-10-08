<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Services\ActivityLogger;

class AdminFaqController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Faq::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('order', 'asc')->get();
        $categories = FaqCategory::orderBy('order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'faqs' => $items,
                'items' => $items,
                'categories' => $categories,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = Faq::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category_id' => $request->categoryId ?: $request->category_id,
            'service_tag' => $request->serviceTag ?: $request->service_tag,
            'order' => intval($request->order ?: 0),
            'is_active' => $request->has('isActive') ? boolval($request->isActive) : true,
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'FAQs',
            entityId: (string) $faq->id,
            details: ['question' => $request->question],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $faq->fresh('category')], 201);
    }

    public function update(Request $request, $id = null)
    {
        $faqId = $id ?: $request->input('id');
        $faq = Faq::find($faqId);
        if (!$faq) {
            return response()->json(['success' => false, 'message' => 'FAQ not found'], 404);
        }

        $updateData = [];
        if ($request->has('question')) $updateData['question'] = $request->question;
        if ($request->has('answer')) $updateData['answer'] = $request->answer;
        if ($request->has('categoryId') || $request->has('category_id')) {
            $updateData['category_id'] = $request->categoryId ?: $request->category_id;
        }
        if ($request->has('serviceTag') || $request->has('service_tag')) {
            $updateData['service_tag'] = $request->serviceTag ?: $request->service_tag;
        }
        if ($request->has('order')) $updateData['order'] = intval($request->order);
        if ($request->has('isActive') || $request->has('is_active')) {
            $updateData['is_active'] = boolval($request->isActive ?? $request->is_active);
        }

        $faq->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'FAQs',
            entityId: (string) $faq->id,
            details: ['question' => $faq->question],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $faq->fresh('category')]);
    }

    public function destroy(Request $request, $id = null)
    {
        $faqId = $id ?: $request->query('id', $request->input('id'));
        $faq = Faq::find($faqId);
        if (!$faq) {
            return response()->json(['success' => false, 'message' => 'FAQ not found'], 404);
        }

        $faq->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'FAQs',
            entityId: (string) $faqId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'FAQ deleted']);
    }
}
