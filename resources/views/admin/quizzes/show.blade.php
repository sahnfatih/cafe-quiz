@extends('layouts.app')

@section('content')
<style>
    .field {
        display:block; width:100%; border-radius:.75rem;
        border:1px solid #334155; background:rgba(30,41,59,.6);
        padding:.6rem .75rem; font-size:.875rem; color:#f1f5f9;
        outline:none; transition:border-color .15s;
    }
    .field:focus { border-color:#38bdf8; }
    .field::placeholder { color:#475569; }
    .opt-badge { display:inline-flex; align-items:center; justify-content:center;
                 width:1.4rem; height:1.4rem; border-radius:.4rem; font-size:.65rem;
                 font-weight:900; flex-shrink:0; }
    .media-section { display:none; }
    .media-section.active { display:block; }
    /* Scrollbar */
    .q-scroll::-webkit-scrollbar { width:4px; }
    .q-scroll::-webkit-scrollbar-track { background:transparent; }
    .q-scroll::-webkit-scrollbar-thumb { background:#334155; border-radius:2px; }
</style>

{{-- Flash --}}
@if(session('status'))
<div class="mb-4 flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3">
    <span class="text-emerald-400 text-lg">✓</span>
    <span class="text-sm text-emerald-200 font-medium">{{ session('status') }}</span>
</div>
@endif

{{-- ─── ÜST BAR ─── --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.quizzes.index') }}"
           class="h-8 w-8 rounded-xl border border-slate-700 bg-slate-800/60
                  flex items-center justify-center text-slate-400
                  hover:border-sky-500 hover:text-sky-300 transition-colors text-sm">
            ←
        </a>
        <div>
            <h1 class="text-lg font-black text-white">{{ $quiz->title }}</h1>
            @if($quiz->description)
                <p class="text-xs text-slate-500 mt-0.5">{{ $quiz->description }}</p>
            @endif
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="openQuizEditModal()"
                class="flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800/60
                       px-3 py-2 text-xs text-slate-300 hover:border-amber-500/50 hover:text-amber-300 transition-colors">
            ✏️ Düzenle
        </button>
        <form action="{{ route('admin.quizzes.startSession', $quiz) }}" method="post">
            @csrf
            <button type="submit"
                    class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500
                           px-4 py-2 text-sm font-black text-white shadow-lg shadow-emerald-500/20
                           hover:from-emerald-400 hover:to-teal-400 active:scale-95 transition-all">
                ▶ Sunumu Başlat
            </button>
        </form>
    </div>
</div>

{{-- ─── ANA İÇERİK ─── --}}
<div class="grid gap-5 xl:grid-cols-[1fr,420px]">

    {{-- ══ SOL: Soru Listesi ══ --}}
    <section class="rounded-2xl border border-slate-700/50 bg-slate-900/60 shadow-xl shadow-slate-900/30">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800/60">
            <h2 class="text-sm font-black text-white">📋 Sorular</h2>
            <span class="text-[11px] text-slate-500 bg-slate-800/60 border border-slate-700/60 rounded-full px-2.5 py-0.5">
                {{ $quiz->questions->count() }} soru
            </span>
        </div>

        @forelse($quiz->questions->sortBy('position') as $q)
        <article class="border-b border-slate-800/40 last:border-b-0 px-5 py-4 hover:bg-slate-800/20 transition-colors">
            <div class="flex items-start gap-3">
                {{-- Numara --}}
                <div class="shrink-0 h-7 w-7 rounded-lg bg-slate-800 border border-slate-700/60
                            flex items-center justify-center text-xs font-black text-slate-400">
                    {{ $q->position }}
                </div>

                {{-- İçerik --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white leading-snug mb-2">{{ $q->text }}</p>

                    {{-- Şıklar --}}
                    <div class="grid grid-cols-2 gap-1 mb-2">
                        @foreach(['A' => 'sky', 'B' => 'violet', 'C' => 'amber', 'D' => 'rose'] as $letter => $color)
                            @php $opt = 'option_'.strtolower($letter); @endphp
                            @if($q->$opt)
                            <div class="flex items-center gap-1.5 text-[11px] rounded-lg px-2 py-1
                                        {{ $q->correct_option === $letter
                                            ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-200'
                                            : 'bg-slate-800/60 border border-slate-700/40 text-slate-400' }}">
                                <span class="opt-badge
                                             {{ $q->correct_option === $letter ? 'bg-emerald-500 text-white' : "bg-{$color}-500/20 text-{$color}-300" }}">
                                    {{ $letter }}
                                </span>
                                <span class="truncate">{{ $q->$opt }}</span>
                                @if($q->correct_option === $letter)
                                    <span class="ml-auto shrink-0 text-emerald-400">✓</span>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Meta --}}
                    <div class="flex flex-wrap items-center gap-2 text-[10px]">
                        <span class="rounded-full bg-sky-500/10 border border-sky-500/20 px-2 py-0.5 text-sky-300 font-semibold">
                            {{ $q->points }}p
                        </span>
                        @if($q->media_type === 'image' && $q->image_path)
                            <span class="rounded-full bg-violet-500/10 border border-violet-500/20 px-2 py-0.5 text-violet-300">
                                🖼 Görsel
                            </span>
                        @elseif($q->media_type === 'video' && $q->video_path)
                            <span class="rounded-full bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 text-amber-300">
                                🎬 Video
                            </span>
                        @elseif($q->media_type === 'youtube')
                            <span class="rounded-full bg-rose-500/10 border border-rose-500/20 px-2 py-0.5 text-rose-300">
                                ▶ YouTube
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Butonlar --}}
                <div class="flex items-center gap-1.5 shrink-0 mt-0.5">
                    <button onclick="openQuestionEditModal({{ $q->id }})"
                            class="h-7 w-7 rounded-lg border border-slate-700 bg-slate-800/60
                                   flex items-center justify-center text-[11px] text-slate-400
                                   hover:border-amber-500/50 hover:text-amber-300 transition-colors"
                            title="Düzenle">✏️</button>
                    <button onclick="confirmDeleteQuestion({{ $q->id }}, {{ $q->position }})"
                            class="h-7 w-7 rounded-lg border border-slate-700 bg-slate-800/60
                                   flex items-center justify-center text-[11px] text-slate-400
                                   hover:border-rose-500/50 hover:text-rose-300 transition-colors"
                            title="Sil">🗑️</button>
                </div>
            </div>
        </article>
        @empty
        <div class="py-16 text-center">
            <div class="text-4xl mb-3">📝</div>
            <p class="text-slate-400 text-sm">Henüz soru yok</p>
            <p class="text-slate-600 text-xs mt-1">Sağ taraftan ilk soruyu ekle</p>
        </div>
        @endforelse
    </section>

    {{-- ══ SAĞ: Soru Ekleme Formu ══ --}}
    <section class="rounded-2xl border border-slate-700/50 bg-slate-900/60 shadow-xl shadow-slate-900/30 h-fit">
        <div class="px-5 py-4 border-b border-slate-800/60">
            <h2 class="text-sm font-black text-white">➕ Yeni Soru Ekle</h2>
        </div>

        <form action="{{ route('admin.quizzes.questions.store', $quiz) }}" method="post"
              enctype="multipart/form-data" class="p-5 space-y-4" id="add-question-form">
            @csrf

            {{-- Soru Metni --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Soru Metni *</label>
                <textarea name="text" rows="3" required placeholder="Soruyu buraya yaz…"
                          class="field resize-none">{{ old('text') }}</textarea>
                @error('text')<p class="text-xs text-rose-400 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Şıklar --}}
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-slate-400">Şıklar</label>
                @foreach(['A' => ['sky','Zorunlu'], 'B' => ['violet','Zorunlu'], 'C' => ['amber','Opsiyonel'], 'D' => ['rose','Opsiyonel']] as $letter => [$color, $req])
                <div class="flex items-center gap-2">
                    <span class="opt-badge bg-{{ $color }}-500/20 text-{{ $color }}-300 border border-{{ $color }}-500/30">{{ $letter }}</span>
                    <input type="text" name="option_{{ strtolower($letter) }}"
                           {{ $letter === 'A' || $letter === 'B' ? 'required' : '' }}
                           placeholder="{{ $letter }} şıkkı{{ $letter === 'C' || $letter === 'D' ? ' (opsiyonel)' : '' }}"
                           value="{{ old('option_'.strtolower($letter)) }}"
                           class="field flex-1">
                </div>
                @endforeach
            </div>

            {{-- Doğru Şık + Puan --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Doğru Şık</label>
                    <select name="correct_option" class="field">
                        @foreach(['A','B','C','D'] as $l)
                            <option value="{{ $l }}" {{ old('correct_option') === $l ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Puan</label>
                    <input type="number" name="points" value="{{ old('points', 100) }}" min="1" class="field">
                </div>
            </div>

            {{-- Medya Tipi --}}
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Medya</label>
                <div class="grid grid-cols-4 gap-1.5" id="media-tabs">
                    @foreach(['none' => ['⊘','Yok','slate'], 'image' => ['🖼','Görsel','violet'], 'video' => ['🎬','Video','amber'], 'youtube' => ['▶','YouTube','rose']] as $val => [$icon, $label, $col])
                    <button type="button" data-media="{{ $val }}"
                            class="media-tab rounded-xl border px-2 py-2 text-center text-[10px] font-bold transition-all
                                   {{ old('media_type','none') === $val
                                       ? "border-{$col}-500/60 bg-{$col}-500/15 text-{$col}-200"
                                       : 'border-slate-700 bg-slate-800/40 text-slate-500 hover:border-slate-600' }}">
                        <div class="text-lg leading-none mb-0.5">{{ $icon }}</div>
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <input type="hidden" name="media_type" id="media-type-input" value="{{ old('media_type','none') }}">
            </div>

            {{-- Görsel Yükleme --}}
            <div class="media-section {{ old('media_type') === 'image' ? 'active' : '' }}" data-for="image">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Görsel Dosyası</label>
                <label class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed
                              border-slate-600 bg-slate-800/30 px-4 py-6 cursor-pointer
                              hover:border-violet-500/50 hover:bg-violet-500/5 transition-colors"
                       id="image-drop-add">
                    <span class="text-2xl" id="image-icon-add">🖼</span>
                    <span class="text-xs text-slate-400" id="image-label-add">Tıkla veya sürükle (max 5MB)</span>
                    <input type="file" name="image" accept="image/*" class="hidden" id="image-input-add">
                </label>
                @error('image')<p class="text-xs text-rose-400 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Video Yükleme --}}
            <div class="media-section {{ old('media_type') === 'video' ? 'active' : '' }}" data-for="video">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Video Dosyası (MP4/WebM, max 100MB)</label>
                <label class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed
                              border-slate-600 bg-slate-800/30 px-4 py-6 cursor-pointer
                              hover:border-amber-500/50 hover:bg-amber-500/5 transition-colors"
                       id="video-drop-add">
                    <span class="text-2xl" id="video-icon-add">🎬</span>
                    <span class="text-xs text-slate-400" id="video-label-add">Tıkla veya sürükle (MP4 / WebM)</span>
                    <input type="file" name="video" accept="video/mp4,video/webm,video/ogg" class="hidden" id="video-input-add">
                </label>
                @error('video')<p class="text-xs text-rose-400 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- YouTube --}}
            <div class="media-section {{ old('media_type') === 'youtube' ? 'active' : '' }} space-y-2" data-for="youtube">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">YouTube URL</label>
                    <input type="url" name="youtube_url" placeholder="https://youtube.com/watch?v=..."
                           value="{{ old('youtube_url') }}" class="field">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Başlangıç (sn)</label>
                        <input type="number" name="youtube_start" min="0" value="{{ old('youtube_start', 0) }}" class="field">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Bitiş (sn)</label>
                        <input type="number" name="youtube_end" min="0" value="{{ old('youtube_end') }}" class="field">
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 rounded-xl
                           bg-gradient-to-r from-sky-500 to-violet-500
                           py-2.5 text-sm font-black text-white shadow-lg shadow-sky-500/20
                           hover:from-sky-400 hover:to-violet-400 active:scale-95 transition-all">
                ➕ Soruyu Ekle
            </button>
        </form>
    </section>
</div>

{{-- ═══════════════════════════════════
     SORU DÜZENLEME MODALİ
════════════════════════════════════ --}}
<div id="question-edit-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="w-full max-w-2xl max-h-[90vh] rounded-2xl border border-slate-700/60
                bg-slate-900 shadow-2xl flex flex-col">

        {{-- Modal Başlık --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800/60 shrink-0">
            <h3 class="text-base font-black text-white">✏️ Soruyu Düzenle</h3>
            <button onclick="closeQuestionEditModal()"
                    class="text-slate-500 hover:text-white text-xl leading-none transition-colors">✕</button>
        </div>

        {{-- Modal Scroll Alanı --}}
        <div class="overflow-y-auto q-scroll flex-1 p-6">
            <form id="question-edit-form" method="post" enctype="multipart/form-data"
                  class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Soru Metni --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Soru Metni *</label>
                    <textarea name="text" id="eq-text" rows="3" required class="field resize-none"></textarea>
                </div>

                {{-- Şıklar --}}
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-400">Şıklar</label>
                    @foreach(['A' => 'sky', 'B' => 'violet', 'C' => 'amber', 'D' => 'rose'] as $letter => $color)
                    <div class="flex items-center gap-2">
                        <span class="opt-badge bg-{{ $color }}-500/20 text-{{ $color }}-300 border border-{{ $color }}-500/30">{{ $letter }}</span>
                        <input type="text" name="option_{{ strtolower($letter) }}"
                               id="eq-option-{{ $letter }}"
                               {{ $letter === 'A' || $letter === 'B' ? 'required' : '' }}
                               placeholder="{{ $letter }} şıkkı"
                               class="field flex-1">
                    </div>
                    @endforeach
                </div>

                {{-- Doğru Şık + Puan --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Doğru Şık</label>
                        <select name="correct_option" id="eq-correct" class="field">
                            @foreach(['A','B','C','D'] as $l)
                                <option value="{{ $l }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Puan</label>
                        <input type="number" name="points" id="eq-points" min="1" class="field">
                    </div>
                </div>

                {{-- Medya Tipi --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Medya</label>
                    <div class="grid grid-cols-4 gap-1.5" id="eq-media-tabs">
                        @foreach(['none' => ['⊘','Yok','slate'], 'image' => ['🖼','Görsel','violet'], 'video' => ['🎬','Video','amber'], 'youtube' => ['▶','YouTube','rose']] as $val => [$icon, $label, $col])
                        <button type="button" data-media="{{ $val }}"
                                class="eq-media-tab rounded-xl border px-2 py-2 text-center text-[10px] font-bold transition-all
                                       border-slate-700 bg-slate-800/40 text-slate-500 hover:border-slate-600">
                            <div class="text-lg leading-none mb-0.5">{{ $icon }}</div>
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="media_type" id="eq-media-type-input" value="none">
                </div>

                {{-- Mevcut Medya Önizleme --}}
                <div id="eq-current-media" class="hidden rounded-xl border border-slate-700/60 bg-slate-800/30 p-3">
                    <p class="text-[10px] text-slate-500 mb-2">Mevcut medya:</p>
                    <div id="eq-media-preview"></div>
                    <p class="text-[10px] text-slate-500 mt-2">Yeni dosya yüklerseniz mevcut silinir.</p>
                </div>

                {{-- Görsel Yükleme --}}
                <div class="media-section" data-for="image" id="eq-image-section">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Yeni Görsel (opsiyonel)</label>
                    <label class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed
                                  border-slate-600 bg-slate-800/30 px-4 py-5 cursor-pointer
                                  hover:border-violet-500/50 transition-colors">
                        <span class="text-2xl" id="eq-image-icon">🖼</span>
                        <span class="text-xs text-slate-400" id="eq-image-label">Tıkla veya sürükle</span>
                        <input type="file" name="image" accept="image/*" class="hidden" id="eq-image-input">
                    </label>
                </div>

                {{-- Video Yükleme --}}
                <div class="media-section" data-for="video" id="eq-video-section">
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Yeni Video (opsiyonel, max 100MB)</label>
                    <label class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed
                                  border-slate-600 bg-slate-800/30 px-4 py-5 cursor-pointer
                                  hover:border-amber-500/50 transition-colors">
                        <span class="text-2xl" id="eq-video-icon">🎬</span>
                        <span class="text-xs text-slate-400" id="eq-video-label">Tıkla veya sürükle (MP4/WebM)</span>
                        <input type="file" name="video" accept="video/mp4,video/webm,video/ogg" class="hidden" id="eq-video-input">
                    </label>
                </div>

                {{-- YouTube --}}
                <div class="media-section space-y-2" data-for="youtube" id="eq-youtube-section">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">YouTube URL</label>
                        <input type="url" name="youtube_url" id="eq-youtube-url"
                               placeholder="https://youtube.com/watch?v=..." class="field">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Başlangıç (sn)</label>
                            <input type="number" name="youtube_start" id="eq-youtube-start" min="0" class="field">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Bitiş (sn)</label>
                            <input type="number" name="youtube_end" id="eq-youtube-end" min="0" class="field">
                        </div>
                    </div>
                </div>

                {{-- Footer Butonları --}}
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeQuestionEditModal()"
                            class="flex-1 rounded-xl border border-slate-700 py-2.5 text-sm text-slate-400
                                   hover:text-white transition-colors">
                        İptal
                    </button>
                    <button type="submit"
                            class="flex-1 rounded-xl bg-gradient-to-r from-sky-500 to-violet-500
                                   py-2.5 text-sm font-black text-white
                                   hover:from-sky-400 hover:to-violet-400 active:scale-95 transition-all">
                        💾 Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════
     QUİZ DÜZENLEME MODALİ
════════════════════════════════════ --}}
<div id="quiz-edit-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl border border-slate-700/60 bg-slate-900 shadow-2xl p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-black text-white">✏️ Quiz Düzenle</h3>
            <button onclick="closeQuizEditModal()" class="text-slate-500 hover:text-white text-xl">✕</button>
        </div>
        <form action="{{ route('admin.quizzes.update', $quiz) }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Başlık *</label>
                <input type="text" name="title" value="{{ $quiz->title }}" required class="field">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Açıklama</label>
                <textarea name="description" rows="3"
                          class="field resize-none">{{ $quiz->description }}</textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeQuizEditModal()"
                        class="flex-1 rounded-xl border border-slate-700 py-2.5 text-sm text-slate-400 hover:text-white transition-colors">
                    İptal
                </button>
                <button type="submit"
                        class="flex-1 rounded-xl bg-sky-500 py-2.5 text-sm font-black text-white
                               hover:bg-sky-400 active:scale-95 transition-all">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Gizli silme formları --}}
<div id="delete-forms" class="hidden"></div>

<script>
/* ══ Medya Tab Sistemi ══ */
function initMediaTabs(tabsContainer, hiddenInput, sections, prefix) {
    const tabs = tabsContainer.querySelectorAll('[data-media]');
    const colors = { none:'slate', image:'violet', video:'amber', youtube:'rose' };

    function activate(val) {
        hiddenInput.value = val;
        tabs.forEach(t => {
            const v = t.dataset.media;
            const c = colors[v] || 'slate';
            t.className = t.className.replace(/border-\S+\/\S+\s?/g,'').replace(/bg-\S+\/\S+\s?/g,'').replace(/text-\S+\s?/g,'');
            if (v === val) {
                t.classList.add(`border-${c}-500/60`, `bg-${c}-500/15`, `text-${c}-200`);
            } else {
                t.classList.add('border-slate-700', 'bg-slate-800/40', 'text-slate-500');
            }
        });
        sections.forEach(s => {
            s.classList.toggle('active', s.dataset.for === val);
        });
    }

    tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.media)));
    activate(hiddenInput.value || 'none');
}

/* ── Add Form Medya Tabs ── */
const addSections = document.querySelectorAll('#add-question-form .media-section');
initMediaTabs(
    document.getElementById('media-tabs'),
    document.getElementById('media-type-input'),
    addSections,
    'add'
);

/* ── Dosya Yükleme Önizleme (Add Form) ── */
function filePreview(inputId, iconId, labelId) {
    document.getElementById(inputId)?.addEventListener('change', function() {
        const f = this.files[0];
        if (!f) return;
        document.getElementById(labelId).textContent = f.name + ' (' + (f.size/1024/1024).toFixed(1) + 'MB)';
        document.getElementById(iconId).textContent = '✅';
    });
}
filePreview('image-input-add', 'image-icon-add', 'image-label-add');
filePreview('video-input-add', 'video-icon-add', 'video-label-add');

/* ── Modal Kapatma ── */
function closeQuizEditModal() {
    document.getElementById('quiz-edit-modal').classList.replace('flex','hidden');
}
function openQuizEditModal() {
    document.getElementById('quiz-edit-modal').classList.replace('hidden','flex');
}
document.getElementById('quiz-edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeQuizEditModal();
});

/* ── Soru Düzenleme Modalı ── */
@php
$questionsMap = $quiz->questions->keyBy('id')->map(function($q) {
    return [
        'id'             => $q->id,
        'text'           => $q->text,
        'option_a'       => $q->option_a,
        'option_b'       => $q->option_b,
        'option_c'       => $q->option_c ?? '',
        'option_d'       => $q->option_d ?? '',
        'correct_option' => $q->correct_option,
        'points'         => $q->points,
        'media_type'     => $q->media_type,
        'image_path'     => $q->image_path,
        'video_path'     => $q->video_path,
        'youtube_url'    => $q->youtube_url ?? '',
        'youtube_start'  => $q->youtube_start ?? 0,
        'youtube_end'    => $q->youtube_end ?? 0,
    ];
});
@endphp
const QUESTIONS = @json($questionsMap);

const QUIZ_ID    = {{ $quiz->id }};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

function openQuestionEditModal(id) {
    const q = QUESTIONS[id];
    if (!q) return;

    /* Form action */
    document.getElementById('question-edit-form').action =
        `/admin/quizzes/${QUIZ_ID}/questions/${id}`;

    /* Alanları doldur */
    document.getElementById('eq-text').value      = q.text;
    document.getElementById('eq-option-A').value  = q.option_a;
    document.getElementById('eq-option-B').value  = q.option_b;
    document.getElementById('eq-option-C').value  = q.option_c || '';
    document.getElementById('eq-option-D').value  = q.option_d || '';
    document.getElementById('eq-correct').value   = q.correct_option;
    document.getElementById('eq-points').value    = q.points;
    document.getElementById('eq-youtube-url').value   = q.youtube_url || '';
    document.getElementById('eq-youtube-start').value = q.youtube_start || 0;
    document.getElementById('eq-youtube-end').value   = q.youtube_end || 0;

    /* Mevcut medya önizleme */
    const previewEl = document.getElementById('eq-current-media');
    const previewContent = document.getElementById('eq-media-preview');
    if (q.media_type === 'image' && q.image_path) {
        previewContent.innerHTML = `<img src="/storage/${q.image_path}" class="rounded-lg max-h-32 object-contain">`;
        previewEl.classList.remove('hidden');
    } else if (q.media_type === 'video' && q.video_path) {
        previewContent.innerHTML = `<video src="/storage/${q.video_path}" controls class="rounded-lg max-h-32 w-full"></video>`;
        previewEl.classList.remove('hidden');
    } else {
        previewEl.classList.add('hidden');
    }

    /* Medya tab'ını aktifleştir */
    const eqTabs = document.getElementById('eq-media-tabs');
    const eqInput = document.getElementById('eq-media-type-input');
    eqInput.value = q.media_type;
    const eqSections = document.querySelectorAll('#question-edit-modal .media-section');
    initMediaTabs(eqTabs, eqInput, eqSections, 'eq');

    /* Dosya önizleme eventleri */
    filePreview('eq-image-input', 'eq-image-icon', 'eq-image-label');
    filePreview('eq-video-input', 'eq-video-icon', 'eq-video-label');

    /* Modalı aç */
    const modal = document.getElementById('question-edit-modal');
    modal.classList.replace('hidden', 'flex');
}

function closeQuestionEditModal() {
    document.getElementById('question-edit-modal').classList.replace('flex','hidden');
}
document.getElementById('question-edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeQuestionEditModal();
});

/* ── Soru Silme ── */
function confirmDeleteQuestion(id, pos) {
    if (!confirm(`#${pos}. soruyu silmek istediğinizden emin misiniz?`)) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/admin/quizzes/${QUIZ_ID}/questions/${id}`;
    form.innerHTML = `<input type="hidden" name="_token" value="${CSRF_TOKEN}">
                      <input type="hidden" name="_method" value="DELETE">`;
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
