<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les champs provenant de l'API SI à la table users.
 *
 * Ces champs sont peuplés par SyncSiUsersJob / SyncAdminUsersJob via upsert
 * sur `kerberos`. Tous nullable pour ne pas bloquer les utilisateurs locaux
 * existants (comptes créés manuellement avant l'intégration SI).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('matricule')->nullable()->after('kerberos');
            $table->string('rank')->nullable()->after('matricule');
            $table->string('phone_number')->nullable()->after('rank');
            $table->string('room_number')->nullable()->after('phone_number');
            $table->string('entity_name')->nullable()->after('room_number');
            $table->timestamp('si_synced_at')->nullable()->after('entity_name')
                ->comment('Date du dernier upsert depuis l\'API SI.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'matricule',
                'rank',
                'phone_number',
                'room_number',
                'entity_name',
                'si_synced_at',
            ]);
        });
    }
};
