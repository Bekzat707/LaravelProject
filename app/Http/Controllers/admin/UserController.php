<?php

namespace App\Http\Controllers\admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\ProfileUpdatedNotification;

class UserController extends Controller
{
    // Показать всех пользователей
    public function index()
    {
        $users = User::paginate(10);  // Пагинация на 10 пользователей
        return view('admin.users.index', compact('users'));
    }

    // Показать форму для добавления нового пользователя
    public function create()
    {
        return view('admin.users.create');
    }

    // Сохранить нового пользователя
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
        ]);
        $updatedField = 'Имя и email'; // Пример, поле может быть динамическим

        // Отправляем уведомление пользователю
        auth()->user()->notify(new ProfileUpdatedNotification($updatedField));

        return redirect()->route('profile')->with('success', 'your profile has been updated and you have been notified.');
    }

    // Показать форму для редактирования пользователя
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    // Обновить данные пользователя
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:user,admin',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'users data has been successfully updated');
    }

    // Удалить пользователя
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'user has been deleted');
    }
    public function downloadPdf(Book $book)
    {
        if (!$book->pdf_path) {
            return redirect()->route('user.books.index')->with('error', 'PDF file was not found.');
        }

        $pdfPath = storage_path('app/public/' . $book->pdf_path);

        if (!file_exists($pdfPath)) {
            return redirect()->route('user.books.index')->with('error', 'file was not found.');
        }

        return response()->download($pdfPath);
    }
}
