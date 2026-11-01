import math
import struct
import wave
import subprocess
import random

SAMPLE_RATE = 44100
DURATION = 20.0  # seconds
TOTAL_SAMPLES = int(SAMPLE_RATE * DURATION)
BPM = 124.0
BEAT_DUR = 60.0 / BPM  # ~0.48387s
SIXTEENTH = BEAT_DUR / 4.0

def clamp(v, min_v=-1.0, max_v=1.0):
    return max(min_v, min(max_v, v))

left_channel = [0.0] * TOTAL_SAMPLES
right_channel = [0.0] * TOTAL_SAMPLES

def add_sample(t_idx, l_val, r_val):
    if 0 <= t_idx < TOTAL_SAMPLES:
        left_channel[t_idx] += l_val
        right_channel[t_idx] += r_val

# Note frequency helper
def mtof(midi_note):
    return 440.0 * (2.0 ** ((midi_note - 69.0) / 12.0))

# MIDI notes
# F3=53, G3=55, Ab3=56, Bb3=58, C4=60, Db4=61, Eb4=63, F4=65, G4=67, Ab4=68, Bb4=70, C5=72, Db5=73, Eb5=75, F5=77, G5=79, Ab5=80, C6=84

# ================= INSTRUMENT 1: WARM ANALOG PAD / STRINGS =================
# Detuned stereo saw + triangle with slow filter movement and chorus
def render_pad(start_time, dur, chord_midi, vel=0.32):
    start_idx = int(start_time * SAMPLE_RATE)
    len_samples = int(dur * SAMPLE_RATE)
    attack = int(0.25 * SAMPLE_RATE)
    release = int(0.35 * SAMPLE_RATE)
    
    for note in chord_midi:
        freq = mtof(note)
        for i in range(len_samples):
            t = i / SAMPLE_RATE
            # Envelope (trapezoid with curved release)
            if i < attack:
                env = (i / attack) ** 1.5
            elif i > len_samples - release:
                rel_t = (len_samples - i) / release
                env = rel_t ** 1.5
            else:
                env = 1.0
            
            # Subtle slow chorused detune
            detune_l = math.sin(2.0 * math.pi * 0.45 * t) * 0.003
            detune_r = math.cos(2.0 * math.pi * 0.52 * t) * 0.003
            
            # Warm analog partials (fundamental + soft 2nd + 3rd)
            sig_l = (math.sin(2.0 * math.pi * freq * (1.0 + detune_l) * t) * 0.65 +
                     math.sin(2.0 * math.pi * freq * 2.0 * t) * 0.22 +
                     math.sin(2.0 * math.pi * freq * 3.0 * t) * 0.12)
            sig_r = (math.sin(2.0 * math.pi * freq * (1.0 - detune_r) * t) * 0.65 +
                     math.sin(2.0 * math.pi * freq * 2.0 * (1.0 + detune_r) * t) * 0.22 +
                     math.sin(2.0 * math.pi * freq * 3.0 * t) * 0.12)
            
            amp = env * vel / len(chord_midi)
            add_sample(start_idx + i, sig_l * amp, sig_r * amp)

# ================= INSTRUMENT 2: MELODIC ANALOG LEAD SYNTH =================
# Expressive singing lead with vibrato, harmonic richness, and ping-pong delay
def render_lead(start_time, dur, midi_note, vel=0.45, pan=0.0):
    start_idx = int(start_time * SAMPLE_RATE)
    len_samples = int(dur * SAMPLE_RATE)
    freq = mtof(midi_note)
    
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        # ADSR Envelope
        att = 0.025
        dec = dur * 0.95
        if t < att:
            env = t / att
        else:
            env = math.exp(-(t - att) * (2.8 / dec))
        
        # Vibrato kicks in after 0.12s
        vib_amp = min(1.0, max(0.0, (t - 0.12) * 5.0)) * 2.5
        inst_freq = freq + math.sin(2.0 * math.pi * 5.8 * t) * vib_amp
        
        # Harmonically rich saw/pulse waveform
        phase = 2.0 * math.pi * inst_freq * t
        sig = (math.sin(phase) * 0.60 + 
               math.sin(phase * 2.0) * 0.25 + 
               math.sin(phase * 3.0) * 0.14 +
               math.sin(phase * 4.0) * 0.08)
        # Soft saturation
        sig = math.tanh(sig * 1.4) * env * vel
        
        l_val = sig * (0.8 - pan * 0.4)
        r_val = sig * (0.8 + pan * 0.4)
        add_sample(start_idx + i, l_val, r_val)
    
    # Ping-Pong Echo Delay (1/8th note and 3/16th note)
    delay_1 = int(0.18 * SAMPLE_RATE)
    delay_2 = int(0.36 * SAMPLE_RATE)
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        env = math.exp(-t * 4.0) * 0.28 * vel
        phase = 2.0 * math.pi * freq * t
        echo = math.sin(phase) * env
        add_sample(start_idx + delay_1 + i, echo * 0.6, echo * 0.1) # Left echo
        add_sample(start_idx + delay_2 + i, echo * 0.1, echo * 0.4) # Right echo

# ================= INSTRUMENT 3: RHODES / ELECTRIC PIANO PLUCKS =================
# Bell-like metallic chime attack with warm decay (marimba / tine hybrid)
def render_electric_piano(start_time, midi_note, vel=0.38, pan=0.0):
    start_idx = int(start_time * SAMPLE_RATE)
    dur = 0.65
    len_samples = int(dur * SAMPLE_RATE)
    freq = mtof(midi_note)
    
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        env = math.exp(-t * 6.2)
        # Tine overtones (non-harmonic bell overtone at 4.2x that decays super fast)
        bell_env = math.exp(-t * 35.0)
        tine = (math.sin(2.0 * math.pi * freq * t) * 0.65 +
                math.sin(2.0 * math.pi * freq * 2.0 * t) * 0.25 +
                math.sin(2.0 * math.pi * freq * 3.0 * t) * 0.10 +
                math.sin(2.0 * math.pi * freq * 4.2 * t) * 0.35 * bell_env)
        
        sig = tine * env * vel
        l_mult = 1.0 - pan * 0.5
        r_mult = 1.0 + pan * 0.5
        add_sample(start_idx + i, sig * l_mult, sig * r_mult)

# ================= INSTRUMENT 4: CRYTALLINE GLOCKENSPIEL / BELL =================
def render_bell(start_time, midi_note, vel=0.35, pan=0.0):
    start_idx = int(start_time * SAMPLE_RATE)
    dur = 1.4
    len_samples = int(dur * SAMPLE_RATE)
    freq = mtof(midi_note)
    
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        env = math.exp(-t * 3.5)
        sparkle = (math.sin(2.0 * math.pi * freq * t) * 0.6 +
                   math.sin(2.0 * math.pi * freq * 2.76 * t) * 0.25 +
                   math.sin(2.0 * math.pi * freq * 5.4 * t) * 0.15)
        sig = sparkle * env * vel
        add_sample(start_idx + i, sig * (1.0 - pan * 0.4), sig * (1.0 + pan * 0.4))

# ================= INSTRUMENT 5: MOOG BASSLINE =================
# Tight, punchy analog bass with resonant filter contour
def render_moog_bass(start_time, dur, midi_note, vel=0.68):
    start_idx = int(start_time * SAMPLE_RATE)
    len_samples = int(dur * SAMPLE_RATE)
    freq = mtof(midi_note)
    
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        # Envelope: punchy decay with sustained foundation
        env = min(1.0, t * 80.0) * math.exp(-t * (1.6 / dur))
        # Filter envelope: starts with rich harmonics and warms down
        filt_amt = math.exp(-t * 14.0)
        
        phase = 2.0 * math.pi * freq * t
        sub = math.sin(phase) * 0.70 # Deep fundamental
        saw = (math.sin(phase * 2.0) * 0.25 + 
               math.sin(phase * 3.0) * 0.15 * filt_amt +
               math.sin(phase * 4.0) * 0.08 * filt_amt)
        
        val = math.tanh((sub + saw) * 1.8) * env * vel
        add_sample(start_idx + i, val * 0.88, val * 0.88)

# ================= INSTRUMENT 6: SHAKER & 16th PERCUSSION =================
random.seed(1337)
def render_shaker(start_time, vel=0.22, pan=0.0):
    start_idx = int(start_time * SAMPLE_RATE)
    dur = 0.065
    len_samples = int(dur * SAMPLE_RATE)
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        noise = (random.random() * 2.0 - 1.0)
        env = math.exp(-t * 70.0)
        # Bandpass shimmer
        bp = noise * math.sin(2.0 * math.pi * 6500.0 * t) * env * vel
        add_sample(start_idx + i, bp * (1.0 - pan * 0.4), bp * (1.0 + pan * 0.4))

# ================= INSTRUMENT 7: PUNCHY ELECTRONIC KICK =================
def render_kick(start_time, boost=1.0):
    start_idx = int(start_time * SAMPLE_RATE)
    dur = 0.26
    len_samples = int(dur * SAMPLE_RATE)
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        # Pitch drop: 150Hz -> 48Hz
        freq = 48.0 + 115.0 * math.exp(-t * 36.0)
        phase = 2.0 * math.pi * (48.0 * t - (115.0 / 36.0) * (math.exp(-t * 36.0) - 1.0))
        amp = math.exp(-t * 11.5)
        # Click transient in first 4ms
        click = (1.0 if i < 180 else 0.0) * math.sin(2.0 * math.pi * 2200.0 * t) * 0.5
        val = (math.sin(phase) + click) * amp * 0.85 * boost
        val = math.tanh(val * 1.5)
        add_sample(start_idx + i, val * 0.95, val * 0.95)

# ================= INSTRUMENT 8: CRISP ELECTRONIC SNARE & CLAP =================
def render_snare(start_time, vel=0.75):
    start_idx = int(start_time * SAMPLE_RATE)
    dur = 0.22
    len_samples = int(dur * SAMPLE_RATE)
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        body = math.sin(2.0 * math.pi * (210.0 * math.exp(-t * 26.0)) * t) * math.exp(-t * 22.0) * 0.45
        noise = (random.random() * 2.0 - 1.0) * math.exp(-t * 14.0) * 0.55
        val = (body + noise) * vel
        # Stereo spread on the noise
        add_sample(start_idx + i, val * 0.90, val * 0.98)

# ================= INSTRUMENT 9: SWEEPS & REVERSE RISERS =================
def render_sweep(start_time, dur=1.0, up=True, vel=0.4):
    start_idx = int(start_time * SAMPLE_RATE)
    len_samples = int(dur * SAMPLE_RATE)
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        prog = (t / dur) if up else (1.0 - t / dur)
        freq = 200.0 + 1600.0 * (prog ** 2.2)
        amp = (prog ** 1.8) * vel
        noise = (random.random() * 2.0 - 1.0) * 0.3
        sig = (math.sin(2.0 * math.pi * freq * t) * 0.7 + noise) * amp
        add_sample(start_idx + i, sig * prog, sig * (1.0 - prog * 0.3))

def render_sub_impact(start_time, vel=0.85):
    start_idx = int(start_time * SAMPLE_RATE)
    dur = 1.2
    len_samples = int(dur * SAMPLE_RATE)
    for i in range(len_samples):
        t = i / SAMPLE_RATE
        sub = math.sin(2.0 * math.pi * (75.0 * math.exp(-t * 3.5)) * t) * math.exp(-t * 2.8) * vel
        add_sample(start_idx + i, sub * 0.9, sub * 0.9)


# ==============================================================================
#                       SONG ARRANGEMENT (20.0 SECONDS)
# ==============================================================================
# Chords:
# Fm9   : [53, 60, 63, 67, 68] (F3, C4, Eb4, G4, Ab4)
# Dbmaj9: [49, 56, 60, 63, 65] (Db3, Ab3, C4, Eb4, F4)
# Abmaj7: [56, 60, 63, 67, 72] (Ab3, C4, Eb4, G4, C5)
# Bbm9  : [58, 61, 65, 68, 72] (Bb3, Db4, F4, Ab4, C5)
# Eb7sus: [51, 58, 61, 63, 68] (Eb3, Bb3, Db4, Eb4, Ab4)

CHORD_FM = [53, 60, 63, 67, 68]
CHORD_DB = [49, 56, 60, 63, 65]
CHORD_AB = [56, 60, 63, 67, 72]
CHORD_BBM = [58, 61, 65, 68, 72]
CHORD_EB = [51, 58, 61, 63, 68]

# ---------------- SCENE A (0.0s - 2.0s): HOOK («ЭТО НЕ ТОЛЬКО УРОКИ») ----------------
render_sub_impact(0.0, vel=0.7)
render_pad(0.0, 2.0, CHORD_FM, vel=0.35)
# Hook electric piano intro arpeggio
render_electric_piano(0.0, 68, vel=0.45, pan=-0.2) # Ab4
render_electric_piano(0.24, 72, vel=0.40, pan=0.2)  # C5
render_electric_piano(0.48, 75, vel=0.42, pan=-0.1) # Eb5
render_electric_piano(0.72, 72, vel=0.38, pan=0.1)  # C5
render_electric_piano(0.96, 68, vel=0.42, pan=-0.2) # Ab4
render_electric_piano(1.20, 67, vel=0.38, pan=0.2)  # G4

render_kick(0.0, boost=0.9)
render_snare(0.48, vel=0.6)
render_kick(0.96, boost=0.9)
render_sweep(1.3, dur=0.7, up=True, vel=0.35)

# ---------------- SCENE B (2.0s - 5.0s): PAIN («ХВАТИТ ТОНУТЬ В РУТИНЕ») ----------------
render_sub_impact(2.0, vel=0.8)
render_pad(2.0, 3.0, CHORD_BBM, vel=0.38)
render_moog_bass(2.0, 1.4, 46, vel=0.7)  # Bb2
render_moog_bass(3.4, 1.5, 48, vel=0.7)  # C3 tension

# Melodic lead motif starts: question phrase
render_lead(2.0, 0.45, 65, vel=0.42, pan=-0.2) # F4
render_lead(2.48, 0.45, 68, vel=0.45, pan=0.2) # Ab4
render_lead(2.96, 0.50, 67, vel=0.42, pan=-0.1) # G4
render_lead(3.44, 0.70, 65, vel=0.48, pan=0.1) # F4

# Drum pulse & shaker
for t_sh in [2.0, 2.24, 2.48, 2.72, 2.96, 3.20, 3.44, 3.68, 3.92, 4.16]:
    render_shaker(t_sh, vel=0.22, pan=random.uniform(-0.3, 0.3))

render_kick(2.0)
render_snare(2.48, vel=0.7)
render_kick(2.96)
render_snare(3.44, vel=0.75)
render_kick(3.92)
render_sweep(4.0, dur=1.0, up=True, vel=0.45) # Singularity collapse riser

# ---------------- SCENE C (5.0s - 7.0s): REVEAL («ЗНАКОМЬТЕСЬ: EDUSFERA») ----------------
# Big uplifting melodic release into Db major!
render_sub_impact(5.0, vel=1.0)
render_pad(5.0, 2.0, CHORD_DB, vel=0.42)
render_moog_bass(5.0, 1.9, 49, vel=0.75) # Db2
render_bell(5.0, 80, vel=0.45, pan=0.0)  # High sparkle Ab5!
render_bell(5.0, 85, vel=0.35, pan=0.2)  # High Db6 sparkle

# Inspiring lead melody
render_lead(5.0, 0.45, 68, vel=0.55, pan=-0.15) # Ab4
render_lead(5.48, 0.40, 72, vel=0.52, pan=0.15)  # C5
render_lead(5.96, 0.40, 73, vel=0.58, pan=-0.1)  # Db5
render_lead(6.44, 0.55, 75, vel=0.62, pan=0.1)   # Eb5

# Electric piano arpeggio 16ths
arpeggio_c = [60, 63, 65, 68, 72, 75, 77, 80]
for idx, note in enumerate(arpeggio_c):
    render_electric_piano(5.0 + idx * 0.12, note, vel=0.35, pan=(idx % 2 * 0.5 - 0.25))

render_kick(5.0, boost=1.1)
render_snare(5.48, vel=0.8)
render_kick(5.96, boost=1.0)
render_snare(6.44, vel=0.85)

for t_sh in [5.0, 5.24, 5.48, 5.72, 5.96, 6.20, 6.44, 6.68]:
    render_shaker(t_sh, vel=0.25, pan=random.uniform(-0.3, 0.3))

# ---------------- SCENE D (7.0s - 14.0s): 3D SHOWCASE (3 SCREENS) ----------------
# Beat D1: Tutor Profile (7.0s - 9.2s) - Ab Major
render_sub_impact(7.0, vel=0.85)
render_pad(7.0, 2.2, CHORD_AB, vel=0.40)
render_moog_bass(7.0, 1.0, 44, vel=0.72) # Ab2
render_moog_bass(8.0, 1.1, 48, vel=0.70) # C3
render_bell(7.0, 77, vel=0.40, pan=-0.2) # F5

# Melodic phrase D1
render_lead(7.0, 0.45, 72, vel=0.55, pan=-0.2) # C5
render_lead(7.48, 0.45, 75, vel=0.52, pan=0.2) # Eb5
render_lead(7.96, 0.45, 77, vel=0.58, pan=-0.1) # F5
render_lead(8.44, 0.65, 75, vel=0.50, pan=0.1)  # Eb5

render_kick(7.0)
render_snare(7.48, vel=0.8)
render_kick(7.96)
render_snare(8.44, vel=0.8)
for t_sh in [7.0, 7.24, 7.48, 7.72, 7.96, 8.20, 8.44, 8.68, 8.92]:
    render_shaker(t_sh, vel=0.24, pan=random.uniform(-0.25, 0.25))

# Beat D2: Lesson Packages & 0 BYN (9.2s - 11.4s) - Bbm9
render_sub_impact(9.2, vel=0.85)
render_pad(9.2, 2.2, CHORD_BBM, vel=0.40)
render_moog_bass(9.2, 1.0, 46, vel=0.72) # Bb2
render_moog_bass(10.2, 1.1, 51, vel=0.70) # Eb3
render_bell(9.2, 75, vel=0.40, pan=0.2)

# Melodic phrase D2 (soaring rhythmic hook)
render_lead(9.2, 0.42, 73, vel=0.54, pan=0.2)  # Db5
render_lead(9.68, 0.42, 75, vel=0.55, pan=-0.2) # Eb5
render_lead(10.16, 0.45, 77, vel=0.60, pan=0.1) # F5
render_lead(10.64, 0.65, 80, vel=0.64, pan=-0.1) # Ab5

render_kick(9.2)
render_snare(9.68, vel=0.8)
render_kick(10.16)
render_snare(10.64, vel=0.8)
for t_sh in [9.2, 9.44, 9.68, 9.92, 10.16, 10.40, 10.64, 10.88, 11.12]:
    render_shaker(t_sh, vel=0.24, pan=random.uniform(-0.25, 0.25))

# Beat D3: Online Booking & Calendar Slot (11.4s - 14.0s) - Eb -> Db
render_sub_impact(11.4, vel=0.85)
render_pad(11.4, 2.5, CHORD_EB, vel=0.42)
render_moog_bass(11.4, 1.2, 51, vel=0.72) # Eb3
render_moog_bass(12.6, 1.3, 49, vel=0.75) # Db3

# Booking slot click at 12.2s: Chime + High Bell Duo
render_bell(12.2, 84, vel=0.55, pan=-0.1) # C6 high chime
render_bell(12.28, 87, vel=0.50, pan=0.2) # Eb6 sparkle
render_electric_piano(12.2, 75, vel=0.45, pan=0.0)

render_lead(11.4, 0.40, 75, vel=0.52, pan=-0.2)
render_lead(11.88, 0.40, 77, vel=0.56, pan=0.2)
render_lead(12.36, 0.45, 80, vel=0.62, pan=-0.1)
render_lead(12.84, 0.65, 82, vel=0.65, pan=0.1) # Bb5 tension!

render_kick(11.4)
render_snare(11.88, vel=0.8)
render_kick(12.36)
render_snare(12.84, vel=0.85)
render_sweep(13.0, dur=1.0, up=True, vel=0.55) # Climax transition riser

for t_sh in [11.4, 11.64, 11.88, 12.12, 12.36, 12.60, 12.84, 13.08, 13.32, 13.56]:
    render_shaker(t_sh, vel=0.25, pan=random.uniform(-0.25, 0.25))

# ---------------- SCENE E (14.0s - 17.0s): EMOTIONAL PAYOFF («МЕНЬШЕ РУТИНЫ. БОЛЬШЕ ВРЕМЕНИ») ----------------
# Full lush orchestration: sweeping strings pad, soaring lead, rolling bass
render_sub_impact(14.0, vel=1.1)
render_pad(14.0, 3.0, CHORD_DB, vel=0.45)
render_moog_bass(14.0, 1.4, 49, vel=0.78) # Db2
render_moog_bass(15.4, 1.5, 44, vel=0.78) # Ab2

render_bell(14.0, 80, vel=0.50, pan=-0.2)
render_bell(14.05, 85, vel=0.42, pan=0.2)

# Grand emotional melodic theme
render_lead(14.0, 0.48, 77, vel=0.65, pan=-0.15) # F5
render_lead(14.48, 0.48, 75, vel=0.60, pan=0.15) # Eb5
render_lead(14.96, 0.48, 73, vel=0.62, pan=-0.1) # Db5
render_lead(15.44, 0.60, 72, vel=0.65, pan=0.1)  # C5
render_lead(16.04, 0.80, 73, vel=0.68, pan=0.0)  # Db5 resolve

# Sparkling mallet arpeggios
ep_payoff = [65, 68, 72, 75, 77, 80, 84, 85]
for idx, note in enumerate(ep_payoff):
    render_electric_piano(14.0 + idx * 0.14, note, vel=0.38, pan=(idx % 2 * 0.4 - 0.2))

render_kick(14.0, boost=1.1)
render_snare(14.48, vel=0.82)
render_kick(14.96, boost=1.05)
render_snare(15.44, vel=0.85)
render_kick(15.92, boost=1.0)
render_snare(16.40, vel=0.8)
render_sweep(16.2, dur=0.8, up=True, vel=0.45)

for t_sh in [14.0, 14.24, 14.48, 14.72, 14.96, 15.20, 15.44, 15.68, 15.92, 16.16, 16.40, 16.64]:
    render_shaker(t_sh, vel=0.26, pan=random.uniform(-0.25, 0.25))

# ---------------- SCENE F (17.0s - 20.0s): BRAND FINALE («EDUSFERA.BY») ----------------
# Majestic brand cadence: Ab major to Db major
render_sub_impact(17.0, vel=1.2)
render_pad(17.0, 2.9, CHORD_AB, vel=0.45)
render_moog_bass(17.0, 2.8, 44, vel=0.80) # Ab2

render_bell(17.0, 80, vel=0.55, pan=-0.1)
render_bell(17.08, 85, vel=0.48, pan=0.2)
render_bell(17.16, 87, vel=0.45, pan=-0.2)

# Final signature melodic resolution
render_lead(17.0, 0.60, 80, vel=0.65, pan=0.0)  # Ab5
render_lead(17.60, 0.60, 77, vel=0.62, pan=-0.1) # F5
render_lead(18.20, 1.20, 72, vel=0.60, pan=0.1)  # C5 (gentle hold)

render_kick(17.0, boost=1.15)
render_snare(17.48, vel=0.85)
render_kick(17.96)
render_snare(18.44, vel=0.75)

# Gentle final shaker
for t_sh in [17.0, 17.24, 17.48, 17.72, 17.96, 18.20]:
    render_shaker(t_sh, vel=0.20, pan=random.uniform(-0.2, 0.2))


# ==============================================================================
#                       MASTERING & SMOOTH FADE OUT
# ==============================================================================
fade_start = 18.5
for i in range(TOTAL_SAMPLES):
    t = i / SAMPLE_RATE
    gain = 1.0
    if t > fade_start:
        fade_ratio = (t - fade_start) / (DURATION - fade_start)
        gain = 1.0 - (fade_ratio ** 1.8)
    
    # Warm analog master limiter
    left_channel[i] = math.tanh(left_channel[i] * 1.15) * gain * 0.92
    right_channel[i] = math.tanh(right_channel[i] * 1.15) * gain * 0.92

wav_file = "promo-tutors/audio_track.wav"
mp3_file = "promo-tutors/audio_track.mp3"

with wave.open(wav_file, "wb") as wf:
    wf.setnchannels(2)
    wf.setsampwidth(2)
    wf.setframerate(SAMPLE_RATE)
    frames = bytearray()
    for i in range(TOTAL_SAMPLES):
        l_int = int(clamp(left_channel[i]) * 32767)
        r_int = int(clamp(right_channel[i]) * 32767)
        frames.extend(struct.pack("<hh", l_int, r_int))
    wf.writeframes(frames)

subprocess.run(["ffmpeg", "-y", "-i", wav_file, "-codec:a", "libmp3lame", "-b:a", "256k", mp3_file], check=True)
print(f"Rich melodic electronic soundtrack rendered: {mp3_file}")
