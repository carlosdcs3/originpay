<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id')->nullable()->after('id');
        });

        // Group existing messages by user_id and create a default conversation
        $usersWithChats = DB::table('support_chats')->select('user_id')->distinct()->get();

        foreach ($usersWithChats as $user) {
            $lastMessage = DB::table('support_chats')
                ->where('user_id', $user->user_id)
                ->orderBy('created_at', 'desc')
                ->first();

            $conversationId = DB::table('support_conversations')->insertGetId([
                'user_id' => $user->user_id,
                'subject' => 'Conversa de suporte',
                'status' => 'pending',
                'last_message_at' => $lastMessage->created_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('support_chats')
                ->where('user_id', $user->user_id)
                ->update(['conversation_id' => $conversationId]);
        }

        // Now that data is migrated, make it foreign and nullable false if safe
        // SQLite doesn't easily allow altering to NOT NULL, but since we updated all, we can enforce foreign key.
        Schema::table('support_chats', function (Blueprint $table) {
            $table->foreign('conversation_id')->references('id')->on('support_conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
