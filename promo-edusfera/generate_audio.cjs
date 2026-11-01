// Generate 30s 44.1kHz 16-bit Stereo PCM WAV with modern lo-fi/synth tech groove and synchronized UI SFX
const fs = require('fs');
const path = require('path');

const sampleRate = 44100;
const duration = 30.0;
const totalSamples = Math.floor(sampleRate * duration);
const numChannels = 2;

// Allocate buffer for 16-bit stereo PCM
const pcmBuffer = Buffer.alloc(totalSamples * numChannels * 2);

function writeWavHeader(buffer, dataLength) {
  const header = Buffer.alloc(44);
  header.write('RIFF', 0);
  header.writeUInt32LE(36 + dataLength, 4);
  header.write('WAVE', 8);
  header.write('fmt ', 12);
  header.writeUInt32LE(16, 16); // SubChunk1Size (16 for PCM)
  header.writeUInt16LE(1, 20); // AudioFormat (1 for PCM)
  header.writeUInt16LE(numChannels, 22);
  header.writeUInt32LE(sampleRate, 24);
  header.writeUInt32LE(sampleRate * numChannels * 2, 28); // ByteRate
  header.writeUInt16LE(numChannels * 2, 32); // BlockAlign
  header.writeUInt16LE(16, 34); // BitsPerSample
  header.write('data', 36);
  header.writeUInt32LE(dataLength, 40);
  return Buffer.concat([header, buffer]);
}

// Chords progression (Eb, Bb, Cm, Ab)
const chordFrequencies = [
  [311.13, 392.00, 466.16], // Eb4, G4, Bb4
  [233.08, 293.66, 349.23], // Bb3, D4, F4
  [261.63, 311.13, 392.00], // C4, Eb4, G4
  [207.65, 261.63, 311.13]  // Ab3, C4, Eb4
];
const bassFrequencies = [77.78, 58.27, 65.41, 51.91]; // Eb2, Bb1, C2, Ab1

// Generate audio samples
for (let i = 0; i < totalSamples; i++) {
  const t = i / sampleRate;

  // 1. Music Layer (Synth Chords + Plucks + Bass + Drums)
  const bpm = 120;
  const beatTime = 60 / bpm; // 0.5s per beat
  const measureTime = beatTime * 4; // 2.0s per measure
  const chordIdx = Math.floor(t / measureTime) % 4;

  // Soft Chord Pad
  let chordSample = 0;
  const chord = chordFrequencies[chordIdx];
  for (let f of chord) {
    chordSample += Math.sin(2 * Math.PI * f * t) * 0.08;
    chordSample += Math.sin(2 * Math.PI * (f * 1.002) * t) * 0.04; // Chorus detune
  }

  // Deep Warm Bass
  const bassFreq = bassFrequencies[chordIdx];
  const bassEnv = Math.exp(-((t % beatTime) * 3));
  const bassSample = (Math.sin(2 * Math.PI * bassFreq * t) + 0.3 * Math.sin(4 * Math.PI * bassFreq * t)) * 0.18 * bassEnv;

  // Pluck Arpeggio (16th notes: 0.125s)
  const arpTime = 0.125;
  const arpStep = Math.floor(t / arpTime) % chord.length;
  const arpFreq = chord[arpStep] * 2; // Octave up
  const arpEnv = Math.exp(-((t % arpTime) * 18));
  const arpSample = Math.sin(2 * Math.PI * arpFreq * t) * 0.06 * arpEnv;

  // Subtle Drum Groove (Hi-hats on 8th notes, Kick on 1 and 3, Snare on 2 and 4)
  const beatStep = (t % (beatTime * 2)) / beatTime;
  let drumSample = 0;
  
  // Kick on beat 0
  const kickTime = t % 1.0;
  if (kickTime < 0.12) {
    const kFreq = 120 * Math.exp(-kickTime * 35) + 45;
    drumSample += Math.sin(2 * Math.PI * kFreq * kickTime) * 0.22 * Math.exp(-kickTime * 20);
  }
  // Snare on beat 0.5
  const snareTime = (t - 0.5) % 1.0;
  if (snareTime >= 0 && snareTime < 0.15) {
    const noise = (Math.random() * 2 - 1);
    drumSample += (noise * 0.12 + Math.sin(2 * Math.PI * 180 * snareTime) * 0.08) * Math.exp(-snareTime * 25);
  }
  // Hi-hat on every 0.25s
  const hatTime = t % 0.25;
  if (hatTime < 0.04) {
    drumSample += (Math.random() * 2 - 1) * 0.04 * Math.exp(-hatTime * 80);
  }

  // 2. Precision Synchronized UI SFX Layer
  let sfxSample = 0;

  // Helper for UI Click (t0)
  function clickSfx(t0) {
    const dt = t - t0;
    if (dt >= 0 && dt < 0.06) {
      return (Math.sin(2 * Math.PI * 1600 * dt) * 0.35 + (Math.random() * 2 - 1) * 0.2) * Math.exp(-dt * 90);
    }
    return 0;
  }

  // Helper for Whoosh Transition (t0)
  function whooshSfx(t0) {
    const dt = t - t0;
    if (dt >= 0 && dt < 0.45) {
      const p = dt / 0.45;
      const freq = 300 + 900 * Math.sin(p * Math.PI);
      return (Math.random() * 2 - 1) * 0.12 * Math.sin(p * Math.PI) + Math.sin(2 * Math.PI * freq * dt) * 0.08 * Math.sin(p * Math.PI);
    }
    return 0;
  }

  // Helper for Success Chime / Bells (t0)
  function chimeSfx(t0, f1 = 880, f2 = 1320) {
    const dt = t - t0;
    if (dt >= 0 && dt < 0.8) {
      return (Math.sin(2 * Math.PI * f1 * dt) * 0.2 + Math.sin(2 * Math.PI * f2 * dt) * 0.18) * Math.exp(-dt * 5);
    }
    return 0;
  }

  // Synced events
  sfxSample += whooshSfx(0.6);
  sfxSample += clickSfx(3.8);   // Scene 1: Click "Запланировать"
  sfxSample += chimeSfx(4.1, 784, 1175); // Modal reveal

  sfxSample += whooshSfx(7.0);  // Transition to Scene 2
  sfxSample += clickSfx(9.8);   // Scene 2: Click "Скопировать"
  sfxSample += chimeSfx(9.9, 1046, 1567); // "Скопировано!"

  sfxSample += whooshSfx(14.0); // Transition into Classroom
  // Marker drawing sound (16.5 - 17.5s)
  if (t >= 16.5 && t <= 17.5) {
    const dt = t - 16.5;
    sfxSample += (Math.random() * 2 - 1) * 0.08 * Math.sin(dt * 25);
  }
  sfxSample += clickSfx(18.7);  // Click on checklist step
  sfxSample += chimeSfx(18.8, 880, 1320); // Checkmark chime
  sfxSample += chimeSfx(19.5, 1175, 1760); // Student reaction "💡 Всё понятно!"

  sfxSample += whooshSfx(22.0); // Transition into Finance
  sfxSample += clickSfx(25.1);  // Click "Сформировать чек НПД"
  sfxSample += chimeSfx(25.2, 587, 880); // Cash register / receipt bell
  sfxSample += chimeSfx(25.4, 880, 1760);

  sfxSample += whooshSfx(26.5); // Outro reveal
  sfxSample += chimeSfx(27.0, 523, 1046); // Brand chord crescendo
  sfxSample += clickSfx(28.5);  // Final CTA click

  // 3. Master Mix & Fade-out at tail
  let master = (chordSample + bassSample + arpSample + drumSample + sfxSample);

  // Fade out smoothly over final 0.6 seconds
  if (t > 29.4) {
    master *= Math.max(0, (30.0 - t) / 0.6);
  }

  // Soft limiter to prevent clipping
  master = Math.tanh(master * 1.1) * 0.85;

  // Convert to 16-bit integer (-32768 to 32767)
  const intSample = Math.max(-32768, Math.min(32767, Math.floor(master * 32767)));

  // Write Left and Right channel
  const byteOffset = i * 4;
  pcmBuffer.writeInt16LE(intSample, byteOffset);     // Left
  pcmBuffer.writeInt16LE(intSample, byteOffset + 2); // Right
}

const wavBuffer = writeWavHeader(pcmBuffer, pcmBuffer.length);
fs.writeFileSync(path.join(__dirname, 'audio/soundtrack.wav'), wavBuffer);
console.log('Successfully generated 30-second soundtrack.wav (' + (wavBuffer.length / 1024 / 1024).toFixed(2) + ' MB)');
