<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotMensaje extends Model
{
    protected $table = 'chatbot_mensajes';

    protected $fillable = ['centro_id', 'user_id', 'pregunta', 'respuesta', 'fuente'];
}
