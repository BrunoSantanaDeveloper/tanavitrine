# Hero da página inicial

O editor fica em **Admin → Configurações → Hero da página inicial** e permite editar textos, cores, mídia de fundo e a sobreposição.

Ao salvar um vídeo, o servidor extrai o primeiro frame como JPEG de até 1600 px de largura. Essa imagem é carregada com prioridade e aparece como fundo e `poster` do vídeo durante o carregamento. Não é necessário enviar uma foto separada.

A imagem é reaproveitada nas edições de texto ou cor; enviar outro vídeo gera uma nova imagem. Se a extração falhar, as configurações anteriores são preservadas e o editor exibe um erro.

## FFmpeg

O servidor precisa do executável `ffmpeg`. A imagem de produção e a imagem de desenvolvimento Sail incluem essa dependência. Para outro ambiente, instale o FFmpeg ou configure `FFMPEG_BINARY` no `.env` com o caminho de um executável local. Caminhos relativos são resolvidos a partir da raiz do projeto.

Para gerar a imagem de um vídeo cadastrado antes desse recurso, abra o editor e clique em **Salvar hero** novamente. A extração acontece apenas no salvamento, sem processamento nas visitas à home.
