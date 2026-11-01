import asyncio
import math
import struct
import wave
import subprocess
import os
import edge_tts

SAMPLE_RATE = 44100
DURATION = 20.0
TOTAL_SAMPLES = int(SAMPLE_RATE * DURATION)

VOICEOVER_PLAN = [
    # (id, start_time, text, rate)
    ("vo_a",  0.15,  "Репетиторство — это не только уроки.", "+16%"),
    ("vo_b",  2.15,  "Хватит тонуть в бесконечных переписках и накладках.", "+18%"),
    ("vo_c",  5.15,  "Знакомьтесь: Edusfera.", "+12%"),
    ("vo_d1", 7.20,  "Ваш профиль и проверенный диплом.", "+16%"),
    ("vo_d2", 9.25,  "Пакеты занятий и ноль рублей комиссии.", "+16%"),
    ("vo_d3", 11.45, "Онлайн-запись в свободные слоты в один клик.", "+18%"),
    ("vo_e",  14.10, "Меньше рутины. Больше времени ученикам.", "+14%"),
    ("vo_f",  17.20, "Создайте свою витрину на Edusfera.by", "+15%"),
]

VOICE = "ru-RU-DmitryNeural"

async def generate_voice_clips():
    os.makedirs("promo-tutors/vo_clips", exist_ok=True)
    for clip_id, start_t, text, rate in VOICEOVER_PLAN:
        mp3_path = f"promo-tutors/vo_clips/{clip_id}.mp3"
        wav_path = f"promo-tutors/vo_clips/{clip_id}.wav"
        comm = edge_tts.Communicate(text, VOICE, rate=rate)
        await comm.save(mp3_path)
        # Convert to 44100Hz mono wav
        subprocess.run(["ffmpeg", "-y", "-i", mp3_path, "-ar", "44100", "-ac", "1", wav_path], 
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, check=True)
        # Probe duration
        dur_out = subprocess.check_output([
            "ffprobe", "-v", "error", "-show_entries", "format=duration",
            "-of", "default=noprint_wrappers=1:nokey=1", wav_path
        ]).decode().strip()
        dur = float(dur_out)
        print(f"[{clip_id}] t={start_t:.2f}s, dur={dur:.2f}s: \"{text}\"")

def mix_master():
    # 1. Load background music
    music_wav = "promo-tutors/audio_track.wav"
    music_left = [0.0] * TOTAL_SAMPLES
    music_right = [0.0] * TOTAL_SAMPLES
    
    with wave.open(music_wav, "rb") as wf:
        n_ch = wf.getnchannels()
        n_frames = wf.getnframes()
        raw = wf.readframes(n_frames)
        fmt = f"<{n_frames * n_ch}h"
        samples = struct.unpack(fmt, raw)
        for i in range(min(n_frames, TOTAL_SAMPLES)):
            if n_ch == 2:
                music_left[i] = samples[i * 2] / 32768.0
                music_right[i] = samples[i * 2 + 1] / 32768.0
            else:
                s = samples[i] / 32768.0
                music_left[i] = s
                music_right[i] = s

    # 2. Prepare voiceover buffers & ducking mask
    vo_left = [0.0] * TOTAL_SAMPLES
    vo_right = [0.0] * TOTAL_SAMPLES
    ducking_gain = [1.0] * TOTAL_SAMPLES

    for clip_id, start_t, _, _ in VOICEOVER_PLAN:
        wav_path = f"promo-tutors/vo_clips/{clip_id}.wav"
        with wave.open(wav_path, "rb") as wf:
            n_frames = wf.getnframes()
            raw = wf.readframes(n_frames)
            samples = struct.unpack(f"<{n_frames}h", raw)
            start_idx = int(start_t * SAMPLE_RATE)
            for i in range(n_frames):
                idx = start_idx + i
                if idx < TOTAL_SAMPLES:
                    v = (samples[i] / 32768.0) * 1.25 # Voice presence gain
                    vo_left[idx] += v
                    vo_right[idx] += v

            # Ducking mask with smooth ramps (attack 30ms, release 120ms)
            duck_start = max(0, start_idx - int(0.04 * SAMPLE_RATE))
            duck_end = min(TOTAL_SAMPLES, start_idx + n_frames + int(0.14 * SAMPLE_RATE))
            for idx in range(duck_start, duck_end):
                ducking_gain[idx] = min(ducking_gain[idx], 0.42) # Duck music by ~7.5 dB

    # Smooth the ducking gain curve (simple moving average filter)
    smooth_gain = [1.0] * TOTAL_SAMPLES
    w = int(0.035 * SAMPLE_RATE)
    cum = sum(ducking_gain[:w])
    for i in range(TOTAL_SAMPLES):
        if i + w < TOTAL_SAMPLES:
            cum += ducking_gain[i + w]
        if i - w >= 0:
            cum -= ducking_gain[i - w]
        smooth_gain[i] = cum / (w * 2)

    # 3. Master mix
    out_left = [0.0] * TOTAL_SAMPLES
    out_right = [0.0] * TOTAL_SAMPLES
    fade_start = 18.5

    for i in range(TOTAL_SAMPLES):
        t = i / SAMPLE_RATE
        gain = 1.0
        if t > fade_start:
            fade_ratio = (t - fade_start) / (DURATION - fade_start)
            gain = 1.0 - (fade_ratio ** 1.8)

        # Mix: Ducked Music + Voiceover
        m_l = music_left[i] * smooth_gain[i]
        m_r = music_right[i] * smooth_gain[i]
        v_l = vo_left[i]
        v_r = vo_right[i]

        sig_l = (m_l + v_l) * gain
        sig_r = (m_r + v_r) * gain

        # Analog Master Soft Limiter
        out_left[i] = math.tanh(sig_l * 1.1) * 0.94
        out_right[i] = math.tanh(sig_r * 1.1) * 0.94

    master_wav = "promo-tutors/master_track.wav"
    master_mp3 = "promo-tutors/audio_track.mp3"

    with wave.open(master_wav, "wb") as wf:
        wf.setnchannels(2)
        wf.setsampwidth(2)
        wf.setframerate(SAMPLE_RATE)
        frames = bytearray()
        for i in range(TOTAL_SAMPLES):
            l_int = int(max(-1.0, min(1.0, out_left[i])) * 32767)
            r_int = int(max(-1.0, min(1.0, out_right[i])) * 32767)
            frames.extend(struct.pack("<hh", l_int, r_int))
        wf.writeframes(frames)

    subprocess.run([
        "ffmpeg", "-y", "-i", master_wav, "-codec:a", "libmp3lame", "-b:a", "256k", master_mp3
    ], check=True)
    print(f"Master broadcast-ready soundtrack with studio voiceover saved: {master_mp3}")

if __name__ == "__main__":
    asyncio.run(generate_voice_clips())
    mix_master()
