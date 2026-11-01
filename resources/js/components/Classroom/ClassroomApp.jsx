import React, { useState, useEffect, useRef } from 'react';
import { 
    Mic, MicOff, Video, VideoOff, PhoneCall, MessageSquare, 
    FileText, Folder, Sparkles, Moon, Sun, LogOut, 
    MoreHorizontal, Undo2, Redo2, Monitor, Lock, Unlock, 
    ChevronLeft, X, Send, Plus, Trash2, Download, Copy, Check, RefreshCw,
    Move, Maximize2, Minimize2, MousePointer, Pencil, Square, Circle, 
    ArrowUpRight, Minus, Type, Eraser, Hand, LayoutGrid, PhoneOff, HelpCircle
} from 'lucide-react';
import { Excalidraw, reconcileElements, getSceneVersion } from '@excalidraw/excalidraw';
import '@excalidraw/excalidraw/index.css';
import { LiveKitConnectionManager } from '../../classroom.js';
import ClassroomOnboardingTour from './ClassroomOnboardingTour.jsx';

export default function ClassroomApp({ config = {} }) {
    // 1. Theme State — DEFAULT TO LIGHT as requested by user
    const [theme, setTheme] = useState(() => {
        const saved = localStorage.getItem('cr-theme');
        return saved === 'dark' ? 'dark' : 'light';
    });

    // 2. Layout & Responsive State
    const [isMobile, setIsMobile] = useState(() => typeof window !== 'undefined' ? window.innerWidth <= 768 : false);
    const [isBoardLocked, setIsBoardLocked] = useState(config.isBoardLocked || false);
    const [isVideoFullscreen, setIsVideoFullscreen] = useState(false);
    const [isVideoHidden, setIsVideoHidden] = useState(false);

    // 2.1 Video Window Drag & Scale State
    const [videoPos, setVideoPos] = useState({ x: null, y: null });
    const [videoScale, setVideoScale] = useState('sm'); // 'sm' | 'md' | 'lg'
    const [isDraggingVideo, setIsDraggingVideo] = useState(false);
    const dragStartRef = useRef({ startX: 0, startY: 0, initPosX: 0, initPosY: 0 });
    const videoIslandRef = useRef(null);

    // 2.2 Excalidraw Active Tool State
    const [activeTool, setActiveTool] = useState('freedraw');

    // 2.3 Fullscreen Call State & Timer
    const [callSeconds, setCallSeconds] = useState(0);
    const [callLayout, setCallLayout] = useState('split'); // 'split' | 'focus'
    const [pipSwap, setPipSwap] = useState(false);
    const [isFlippingCamera, setIsFlippingCamera] = useState(false);

    // 3. Media & Call State
    const [isMicOn, setIsMicOn] = useState(true);
    const [isCameraOn, setIsCameraOn] = useState(true);
    const [isScreenSharing, setIsScreenSharing] = useState(false);
    const [partnerSpeaking, setPartnerSpeaking] = useState(false);
    const [partnerVideoOn, setPartnerVideoOn] = useState(false);
    const [partnerConnected, setPartnerConnected] = useState(false);
    const [linkCopied, setLinkCopied] = useState(false);

    // 4. Drawer & Modals State
    const [activeTab, setActiveTab] = useState(null); // 'chat' | 'notes' | 'files' | 'ai' | 'report' | null
    const [mobileMoreOpen, setMobileMoreOpen] = useState(false);
    const [exitModalOpen, setExitModalOpen] = useState(false);
    const [unreadChatCount, setUnreadChatCount] = useState(0);
    const [isTourOpen, setIsTourOpen] = useState(false);

    // Initial check for classroom onboarding tour
    useEffect(() => {
        try {
            const tourSeen = localStorage.getItem('edusfera_classroom_tour_completed_v1');
            const urlParams = new URLSearchParams(window.location.search);
            const forceTour = urlParams.get('tour') === '1';
            if (!tourSeen || forceTour) {
                setIsTourOpen(true);
            }
        } catch (_) {}
    }, []);

    // 5. Data Collections
    const [chatMessages, setChatMessages] = useState([]);
    const [messageInput, setMessageInput] = useState('');
    const [notes, setNotes] = useState([]);
    const [noteInput, setNoteInput] = useState('');
    const [notesLoading, setNotesLoading] = useState(false);
    const [files, setFiles] = useState([]);
    const [filesLoading, setFilesLoading] = useState(false);
    const [isUploadingFile, setIsUploadingFile] = useState(false);
    const fileInputRef = useRef(null);
    const [aiMessages, setAiMessages] = useState(config.aiChatHistory || [
        { role: 'assistant', text: 'Здравствуйте! Я ваш ИИ-помощник Edusfera. Могу составить план урока, разобрать сложную задачу или сгенерировать тест по ЦТ.' }
    ]);
    const [aiInput, setAiInput] = useState('');
    const [isAiLoading, setIsAiLoading] = useState(false);

    // Excalidraw API & Media Refs
    const [excalidrawAPI, setExcalidrawAPI] = useState(null);
    const [cameraFacing, setCameraFacing] = useState('user'); // 'user' | 'environment'
    const localVideoRef = useRef(null);
    const remoteVideoRef = useRef(null);
    const localFsVideoRef = useRef(null);
    const remoteFsVideoRef = useRef(null);
    const liveKitRef = useRef(null);
    const localVideoTrackRef = useRef(null);
    const remoteVideoTrackRef = useRef(null);

    // Excalidraw Real-time Sync & CRDT Refs
    const isReceivingRemoteUpdate = useRef(false);
    const lastBroadcastedVersion = useRef(0);
    const lastBroadcastTime = useRef(0);
    const trailingTimer = useRef(null);
    const latestElementsRef = useRef(null);
    const latestVersionRef = useRef(0);
    const lastPointerBroadcast = useRef(0);

    // Re-attach video tracks to mounted video elements
    const attachTracks = () => {
        requestAnimationFrame(() => {
            if (localVideoTrackRef.current) {
                if (localVideoRef.current) {
                    try { localVideoTrackRef.current.attach(localVideoRef.current); } catch (_) {}
                    localVideoRef.current.play?.().catch?.(() => {});
                }
                if (localFsVideoRef.current) {
                    try { localVideoTrackRef.current.attach(localFsVideoRef.current); } catch (_) {}
                    localFsVideoRef.current.play?.().catch?.(() => {});
                }
            }
            if (remoteVideoTrackRef.current) {
                if (remoteVideoRef.current) {
                    try { remoteVideoTrackRef.current.attach(remoteVideoRef.current); } catch (_) {}
                    remoteVideoRef.current.play?.().catch?.(() => {});
                }
                if (remoteFsVideoRef.current) {
                    try { remoteVideoTrackRef.current.attach(remoteFsVideoRef.current); } catch (_) {}
                    remoteFsVideoRef.current.play?.().catch?.(() => {});
                }
            }
        });
    };

    // Window Resize Tracker
    useEffect(() => {
        const handleResize = () => {
            setIsMobile(window.innerWidth <= 768);
        };
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    // Expose Excalidraw API globally
    useEffect(() => {
        if (excalidrawAPI) {
            window.excalidrawAPI = excalidrawAPI;
        }
    }, [excalidrawAPI]);

    // Track attachment on view state change
    useEffect(() => {
        attachTracks();
    }, [isVideoFullscreen, isCameraOn, partnerVideoOn]);

    // Call Seconds Counter
    useEffect(() => {
        const timer = setInterval(() => {
            setCallSeconds(prev => prev + 1);
        }, 1000);
        return () => clearInterval(timer);
    }, []);

    const formatCallTime = (secs) => {
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
    };

    // Video Width by Scale Preset
    const getVideoWidth = () => {
        if (isMobile) {
            if (videoScale === 'lg') return 220;
            if (videoScale === 'md') return 165;
            return 115;
        } else {
            if (videoScale === 'lg') return 380;
            if (videoScale === 'md') return 290;
            return 210;
        }
    };

    const cycleVideoScale = () => {
        setVideoScale(prev => {
            if (prev === 'sm') return 'md';
            if (prev === 'md') return 'lg';
            return 'sm';
        });
    };

    // Tool Selector for Excalidraw
    const selectTool = (toolType) => {
        setActiveTool(toolType);
        if (excalidrawAPI) {
            try {
                excalidrawAPI.setActiveTool({ type: toolType });
            } catch (e) {
                console.warn('[ClassroomApp] setActiveTool error:', e);
            }
        }
    };

    // Video Window Dragging Listeners
    const handleDragStart = (e) => {
        if (e.type === 'mousedown' && e.button !== 0) return;
        if (e.target.closest('button')) return;

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const el = videoIslandRef.current;
        if (!el) return;
        const rect = el.getBoundingClientRect();

        dragStartRef.current = {
            startX: clientX,
            startY: clientY,
            initPosX: rect.left,
            initPosY: rect.top,
        };
        setIsDraggingVideo(true);
    };

    useEffect(() => {
        if (!isDraggingVideo) return;

        const onMove = (e) => {
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;

            const deltaX = clientX - dragStartRef.current.startX;
            const deltaY = clientY - dragStartRef.current.startY;

            const currentW = getVideoWidth();
            const minX = 8;
            const maxX = Math.max(8, window.innerWidth - currentW - 8);
            const minY = 8;
            const maxY = Math.max(8, window.innerHeight - 100);

            const newX = Math.min(Math.max(minX, dragStartRef.current.initPosX + deltaX), maxX);
            const newY = Math.min(Math.max(minY, dragStartRef.current.initPosY + deltaY), maxY);

            setVideoPos({ x: Math.round(newX), y: Math.round(newY) });
        };

        const onEnd = () => {
            setIsDraggingVideo(false);
        };

        window.addEventListener('mousemove', onMove, { passive: true });
        window.addEventListener('mouseup', onEnd);
        window.addEventListener('touchmove', onMove, { passive: true });
        window.addEventListener('touchend', onEnd);
        window.addEventListener('touchcancel', onEnd);

        return () => {
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onEnd);
            window.removeEventListener('touchmove', onMove);
            window.removeEventListener('touchend', onEnd);
            window.removeEventListener('touchcancel', onEnd);
        };
    }, [isDraggingVideo, videoScale, isMobile]);

    // ═══════════════════════════════════════════════════════════
    // EXCALIDRAW REAL-TIME COLLABORATION & SYNC ENGINE
    // ═══════════════════════════════════════════════════════════
    const broadcastScene = (elements, version) => {
        if (liveKitRef.current && typeof liveKitRef.current.sendData === 'function') {
            liveKitRef.current.sendData({
                type: 'wb_excalidraw_sync',
                elements: elements,
                version: version,
            });
        }
    };

    const handleExcalidrawChange = (elements, appState) => {
        // Track local tool changes
        if (appState?.activeTool?.type && appState.activeTool.type !== activeTool) {
            setActiveTool(appState.activeTool.type);
        }

        // Do not broadcast if we are currently applying remote changes or board is locked
        if (!elements || isReceivingRemoteUpdate.current || isBoardLocked) return;

        const currentVersion = getSceneVersion(elements);
        if (currentVersion === lastBroadcastedVersion.current) return;

        latestElementsRef.current = elements;
        latestVersionRef.current = currentVersion;

        const now = performance.now();
        const elapsed = now - lastBroadcastTime.current;
        const THROTTLE_MS = 35; // ~28fps for silky smooth real-time pen strokes

        if (elapsed >= THROTTLE_MS) {
            if (trailingTimer.current) {
                clearTimeout(trailingTimer.current);
                trailingTimer.current = null;
            }
            lastBroadcastTime.current = now;
            lastBroadcastedVersion.current = currentVersion;
            broadcastScene(elements, currentVersion);
        } else {
            if (!trailingTimer.current) {
                trailingTimer.current = setTimeout(() => {
                    trailingTimer.current = null;
                    lastBroadcastTime.current = performance.now();
                    lastBroadcastedVersion.current = latestVersionRef.current;
                    broadcastScene(latestElementsRef.current, latestVersionRef.current);
                }, THROTTLE_MS - elapsed);
            }
        }
    };

    const handlePointerUpdate = (payload) => {
        if (!payload?.pointer || !liveKitRef.current) return;
        const now = performance.now();
        if (now - lastPointerBroadcast.current < 40) return; // ~25fps throttle
        lastPointerBroadcast.current = now;

        liveKitRef.current.sendData({
            type: 'wb_excalidraw_pointer',
            x: payload.pointer.x,
            y: payload.pointer.y,
            button: payload.button || 'up',
            userId: config.userName || 'user',
            userName: config.userName || 'Собеседник',
            color: config.userRole === 'tutor' ? '#7D39EB' : '#3B82F6'
        }, { reliable: false });
    };

    const applyRemoteElements = (remoteElements) => {
        if (!remoteElements || !Array.isArray(remoteElements) || !excalidrawAPI) return;
        isReceivingRemoteUpdate.current = true;
        try {
            if (remoteElements.length === 0) {
                lastBroadcastedVersion.current = 0;
                excalidrawAPI.updateScene({
                    elements: [],
                    commitToHistory: false,
                });
                return;
            }

            const currentElements = excalidrawAPI.getSceneElements() || [];
            const currentAppState = excalidrawAPI.getAppState() || {};

            const reconciled = reconcileElements(currentElements, remoteElements, currentAppState);
            const newVersion = getSceneVersion(reconciled);
            lastBroadcastedVersion.current = newVersion;

            excalidrawAPI.updateScene({
                elements: reconciled,
                commitToHistory: false,
            });
        } catch (err) {
            console.warn('[ClassroomApp] Remote scene reconcile error:', err);
        } finally {
            requestAnimationFrame(() => {
                isReceivingRemoteUpdate.current = false;
            });
        }
    };

    const applyRemotePointer = (payload) => {
        if (!payload || !excalidrawAPI) return;
        try {
            const collaborators = new Map(excalidrawAPI.getAppState()?.collaborators || []);
            collaborators.set(payload.userId || 'peer', {
                pointer: { x: payload.x, y: payload.y },
                button: payload.button || 'up',
                username: payload.userName || 'Собеседник',
                color: {
                    background: payload.color || '#3B82F6',
                    stroke: payload.color || '#3B82F6',
                },
            });
            excalidrawAPI.updateScene({ collaborators });
        } catch (_) {}
    };

    // LiveKit WebRTC Video/Audio Connection Lifecycle
    useEffect(() => {
        let isCancelled = false;
        if (!config.liveKitWsUrl || !config.liveKitToken) {
            console.warn('[ClassroomApp] LiveKit credentials not provided in config');
            return;
        }

        const lk = new LiveKitConnectionManager({
            wsUrl: config.liveKitWsUrl,
            token: config.liveKitToken,
            userRole: config.userRole,
            userName: config.userName,
            onLocalTrack: (track) => {
                if (isCancelled) return;
                console.log('[ClassroomApp] onLocalTrack:', track.kind);
                if (track.kind === 'video') {
                    localVideoTrackRef.current = track;
                    setIsCameraOn(true);
                }
                if (track.kind === 'audio') {
                    setIsMicOn(true);
                }
                attachTracks();
            },
            onRemoteTrack: (track, pub, participant) => {
                if (isCancelled) return;
                console.log('[ClassroomApp] onRemoteTrack:', track.kind, participant.identity);
                if (track.kind === 'video') {
                    remoteVideoTrackRef.current = track;
                    setPartnerVideoOn(true);
                    setPartnerConnected(true);
                } else if (track.kind === 'audio') {
                    const audioEl = track.attach();
                    audioEl.id = 'lk-audio-' + (participant.identity || 'remote');
                    audioEl.style.display = 'none';
                    document.body.appendChild(audioEl);
                }
                attachTracks();
            },
            onRemoteTrackUnsubscribed: (track) => {
                if (track.kind === 'video') {
                    setPartnerVideoOn(false);
                    remoteVideoTrackRef.current = null;
                }
            },
            onRemoteTrackMuted: (isMuted) => {
                setPartnerVideoOn(!isMuted);
            },
            onData: (data, participant) => {
                if (!data || !data.type) return;
                console.log('[ClassroomApp] onData received:', data.type);
                if (data.type === 'wb_excalidraw_sync') {
                    applyRemoteElements(data.elements);
                } else if (data.type === 'wb_excalidraw_pointer') {
                    applyRemotePointer(data);
                } else if (data.type === 'wb_excalidraw_request_sync') {
                    if (excalidrawAPI) {
                        const currentElements = excalidrawAPI.getSceneElements() || [];
                        if (currentElements.length > 0) {
                            broadcastScene(currentElements, getSceneVersion(currentElements));
                        }
                    }
                } else if (data.type === 'wb_lock_toggle') {
                    setIsBoardLocked(data.locked);
                    if (excalidrawAPI) {
                        excalidrawAPI.updateScene({ appState: { viewModeEnabled: data.locked } });
                    }
                } else if (data.type === 'chat_message') {
                    if (data.message) {
                        setChatMessages(prev => [...prev, data.message]);
                        setUnreadChatCount(prev => prev + 1);
                    }
                }
            },
            onParticipantJoined: (p) => {
                console.log('[ClassroomApp] Partner joined:', p.identity);
                setPartnerConnected(true);
                if (excalidrawAPI) {
                    const currentElements = excalidrawAPI.getSceneElements() || [];
                    if (currentElements.length > 0) {
                        broadcastScene(currentElements, getSceneVersion(currentElements));
                    } else {
                        liveKitRef.current?.sendData({ type: 'wb_excalidraw_request_sync' });
                    }
                }
            },
            onParticipantLeft: () => {
                console.log('[ClassroomApp] Partner left');
                setPartnerConnected(false);
                setPartnerVideoOn(false);
                remoteVideoTrackRef.current = null;
                document.querySelectorAll("audio[id^='lk-audio-']").forEach(el => el.remove());
            },
            onActiveSpeakers: (speakers) => {
                const partnerSpeaking = speakers.some(s => !s.isLocal);
                setPartnerSpeaking(partnerSpeaking);
            }
        });

        liveKitRef.current = lk;

        lk.start().then(() => {
            console.log('[ClassroomApp] LiveKit connected and ready');
            attachTracks();
            // Request existing whiteboard state from remote peer if already present
            lk.sendData({ type: 'wb_excalidraw_request_sync' });
        }).catch(err => {
            console.error('[ClassroomApp] LiveKit start error:', err);
        });

        return () => {
            isCancelled = true;
            document.querySelectorAll("audio[id^='lk-audio-']").forEach(el => el.remove());
            if (liveKitRef.current) {
                try { liveKitRef.current.room?.disconnect(true); } catch (_) {}
                liveKitRef.current = null;
            }
        };
    }, [config.liveKitWsUrl, config.liveKitToken]);

    // Theme Switcher Effect
    const toggleTheme = () => {
        const nextTheme = theme === 'light' ? 'dark' : 'light';
        setTheme(nextTheme);
        localStorage.setItem('cr-theme', nextTheme);
        if (excalidrawAPI) {
            excalidrawAPI.updateScene({ appState: { theme: nextTheme } });
        }
    };

    // Copy Invite Link
    const copyInviteLink = () => {
        if (!config.studentInviteUrl) return;
        navigator.clipboard.writeText(config.studentInviteUrl).then(() => {
            setLinkCopied(true);
            setTimeout(() => setLinkCopied(false), 2000);
        });
    };

    // Toggle Mic / Cam / Flip
    const toggleMic = async () => {
        const next = !isMicOn;
        setIsMicOn(next);
        if (liveKitRef.current) {
            await liveKitRef.current.setMicEnabled(next);
        }
    };

    const toggleCamera = async () => {
        const next = !isCameraOn;
        setIsCameraOn(next);
        if (liveKitRef.current) {
            await liveKitRef.current.setCameraEnabled(next);
            attachTracks();
        }
    };

    const handleFlipCamera = async () => {
        setIsFlippingCamera(true);
        if (liveKitRef.current) {
            const nextFacing = await liveKitRef.current.flipCamera();
            if (nextFacing) setCameraFacing(nextFacing);
            attachTracks();
        }
        setTimeout(() => setIsFlippingCamera(false), 500);
    };

    // Undo / Redo
    const handleUndo = () => {
        if (excalidrawAPI?.history) {
            excalidrawAPI.history.undo();
        } else if (window.undoExcalidraw) {
            window.undoExcalidraw();
        }
    };

    const handleRedo = () => {
        if (excalidrawAPI?.history) {
            excalidrawAPI.history.redo();
        } else if (window.redoExcalidraw) {
            window.redoExcalidraw();
        }
    };

    // Initial Chat History Loader
    useEffect(() => {
        if (!config.lessonId) return;
        fetch(`/classroom/${config.lessonId}/chat`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                setChatMessages(data.map(m => ({
                    id: m.id,
                    sender_id: m.userId,
                    sender_name: m.userName,
                    message: m.text,
                    created_at: m.time
                })));
            }
        })
        .catch(() => {});
    }, [config.lessonId]);

    // Drawer Tabs Dynamic Loader
    const loadNotes = async () => {
        if (!config.lessonId) return;
        setNotesLoading(true);
        try {
            const res = await fetch(`/classroom/${config.lessonId}/notes`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (Array.isArray(data)) setNotes(data);
        } catch (_) {}
        finally { setNotesLoading(false); }
    };

    const addNote = async (e) => {
        e?.preventDefault();
        if (!noteInput.trim() || !config.lessonId) return;
        const text = noteInput.trim();
        setNoteInput('');
        try {
            const res = await fetch(`/classroom/${config.lessonId}/notes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: text, is_shared: true })
            });
            const data = await res.json();
            if (data.note) {
                setNotes(prev => [{
                    id: data.note.id,
                    text: data.note.content,
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                }, ...prev]);
            }
        } catch (err) {
            console.warn('[Notes] Save note error:', err);
        }
    };

    const loadFiles = async () => {
        if (!config.lessonId) return;
        setFilesLoading(true);
        try {
            const res = await fetch(`/classroom/${config.lessonId}/files`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (Array.isArray(data)) setFiles(data);
        } catch (_) {}
        finally { setFilesLoading(false); }
    };

    const handleFileUpload = async (e) => {
        const file = e.target.files?.[0];
        if (!file || !config.lessonId) return;
        setIsUploadingFile(true);
        const formData = new FormData();
        formData.append('file', file);
        try {
            const res = await fetch(`/classroom/${config.lessonId}/files`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();
            if (data.file) {
                setFiles(prev => [{
                    id: data.file.id,
                    name: data.file.original_name,
                    size: (data.file.size_bytes / 1024).toFixed(1) + ' КБ',
                    url: `/classroom/${config.lessonId}/files/${data.file.id}/download`
                }, ...prev]);
            }
        } catch (err) {
            console.warn('[Files] Upload error:', err);
        } finally {
            setIsUploadingFile(false);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    };

    useEffect(() => {
        if (activeTab === 'notes') loadNotes();
        if (activeTab === 'files') loadFiles();
    }, [activeTab]);

    // Send Chat Message
    const sendMessage = async (e) => {
        e?.preventDefault();
        if (!messageInput.trim()) return;
        const text = messageInput.trim();
        setMessageInput('');
        const newMsg = {
            id: Date.now(),
            sender_id: config.userId,
            sender_name: config.userName,
            message: text,
            created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        };
        setChatMessages(prev => [...prev, newMsg]);
        liveKitRef.current?.sendData({
            type: 'chat_message',
            message: newMsg
        });

        try {
            await fetch(`/classroom/${config.lessonId}/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });
        } catch (err) {
            console.warn('[Chat] Failed to send message:', err);
        }
    };

    // Send AI Message
    const sendAiMessage = async (e) => {
        e?.preventDefault();
        if (!aiInput.trim() || isAiLoading) return;
        const text = aiInput.trim();
        setAiInput('');
        setAiMessages(prev => [...prev, { role: 'user', text }]);
        setIsAiLoading(true);

        try {
            const res = await fetch(`/classroom/${config.lessonId}/ai-chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    message: text,
                    history: aiMessages.slice(-10).map(m => ({ role: m.role, text: m.text }))
                })
            });
            const data = await res.json();
            if (data.reply) {
                setAiMessages(prev => [...prev, { role: 'assistant', text: data.reply }]);
            }
        } catch (err) {
            setAiMessages(prev => [...prev, { role: 'assistant', text: 'Ошибка ответа ИИ. Попробуйте ещё раз.' }]);
        } finally {
            setIsAiLoading(false);
        }
    };

    const isLight = theme === 'light';

    return (
        <div className={`cr-viewport cr-app-root ${isLight ? 'theme-light' : 'theme-dark'}`} style={{
            position: 'fixed',
            inset: 0,
            width: '100vw',
            height: '100dvh',
            maxHeight: '100dvh',
            overflow: 'hidden',
            backgroundColor: isLight ? '#FFFFFF' : '#0B0B0E',
            color: isLight ? '#0F172A' : '#FFFFFF',
            fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            userSelect: 'none'
        }}>
            {/* ═══════════════════════════════════════════════════ */}
            {/* 1. EXCALIDRAW WHITEBOARD SURFACE                   */}
            {/* ═══════════════════════════════════════════════════ */}
            <div className="cr-excalidraw-surface" style={{
                position: 'absolute',
                inset: 0,
                width: '100%',
                height: '100%',
                zIndex: 1,
            }}>
                <Excalidraw
                    excalidrawAPI={api => setExcalidrawAPI(api)}
                    theme={theme}
                    langCode="ru-RU"
                    viewModeEnabled={isBoardLocked}
                    onChange={handleExcalidrawChange}
                    onPointerUpdate={handlePointerUpdate}
                    UIOptions={{
                        canvasActions: {
                            loadScene: false,
                            saveAsImage: true,
                            export: { saveFileToDisk: true },
                            theme: false,
                            clearCanvas: true,
                            changeViewBackgroundColor: true,
                        }
                    }}
                />
            </div>

            {/* ═══════════════════════════════════════════════════ */}
            {/* 2. TOP HEADER ISLAND                               */}
            {/* ═══════════════════════════════════════════════════ */}
            {!isVideoFullscreen && (
                <header style={{
                    position: 'absolute',
                    top: isMobile ? 'max(8px, env(safe-area-inset-top))' : '16px',
                    left: isMobile ? '8px' : '16px',
                    right: isMobile ? '8px' : 'auto',
                    height: isMobile ? '44px' : '48px',
                    padding: isMobile ? '0 10px' : '0 14px',
                    borderRadius: '20px',
                    backgroundColor: isLight ? 'rgba(255, 255, 255, 0.90)' : 'rgba(18, 18, 20, 0.90)',
                    backdropFilter: 'blur(20px) saturate(160%)',
                    WebkitBackdropFilter: 'blur(20px) saturate(160%)',
                    border: isLight ? '1px solid rgba(0, 0, 0, 0.08)' : '1px solid rgba(255, 255, 255, 0.15)',
                    boxShadow: isLight ? '0 10px 30px rgba(0, 0, 0, 0.08)' : '0 16px 40px rgba(0, 0, 0, 0.6)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    gap: '10px',
                    zIndex: 40,
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0 }}>
                        <a href="/admin" title="В панель управления" style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            width: '32px',
                            height: '32px',
                            borderRadius: '10px',
                            backgroundColor: isLight ? 'rgba(0, 0, 0, 0.04)' : 'rgba(255, 255, 255, 0.08)',
                            color: isLight ? '#0F172A' : '#FFFFFF',
                            textDecoration: 'none'
                        }}>
                            <ChevronLeft size={20} />
                        </a>
                        <div style={{ display: 'flex', flexDirection: 'column', minWidth: 0 }}>
                            <span style={{
                                fontSize: '13px',
                                fontWeight: 700,
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                maxWidth: isMobile ? '120px' : '200px',
                                color: isLight ? '#0F172A' : '#FFFFFF'
                            }}>
                                {config.subject || 'Онлайн-урок'}
                            </span>
                            <span style={{
                                fontSize: '11px',
                                color: isLight ? '#64748B' : '#94A3B8',
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                maxWidth: isMobile ? '120px' : '200px'
                            }}>
                                {config.partnerName || 'Собеседник'}
                            </span>
                        </div>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        {/* Board Lock Pill */}
                        <button 
                            type="button"
                            onClick={() => {
                                if (config.userRole === 'tutor') {
                                    const next = !isBoardLocked;
                                    setIsBoardLocked(next);
                                    if (excalidrawAPI) excalidrawAPI.updateScene({ appState: { viewModeEnabled: next } });
                                }
                            }}
                            title={config.userRole === 'tutor' ? (isBoardLocked ? 'Разблокировать доску для ученика' : 'Заблокировать доску') : 'Статус доступа к доске'}
                            style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: '4px',
                                padding: '4px 8px',
                                borderRadius: '12px',
                                fontSize: '11px',
                                fontWeight: 600,
                                border: 'none',
                                cursor: config.userRole === 'tutor' ? 'pointer' : 'default',
                                backgroundColor: isBoardLocked 
                                    ? (isLight ? 'rgba(239, 68, 68, 0.12)' : 'rgba(239, 68, 68, 0.22)')
                                    : (isLight ? 'rgba(16, 185, 129, 0.12)' : 'rgba(16, 185, 129, 0.22)'),
                                color: isBoardLocked ? '#EF4444' : (isLight ? '#059669' : '#10B981')
                            }}>
                            {isBoardLocked ? <Lock size={12} /> : <Unlock size={12} />}
                            <span>{isBoardLocked ? 'Замок' : 'Открыто'}</span>
                        </button>

                        {/* Theme Toggle */}
                        <button
                            type="button"
                            onClick={toggleTheme}
                            title={isLight ? 'Включить тёмную тему' : 'Включить светлую тему'}
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                width: '30px',
                                height: '30px',
                                borderRadius: '10px',
                                border: 'none',
                                cursor: 'pointer',
                                backgroundColor: isLight ? 'rgba(0, 0, 0, 0.04)' : 'rgba(255, 255, 255, 0.08)',
                                color: isLight ? '#F59E0B' : '#FCD34D'
                            }}>
                            {isLight ? <Moon size={15} color="#475569" /> : <Sun size={15} />}
                        </button>

                        {/* Help / Onboarding Tour */}
                        <button
                            type="button"
                            onClick={() => setIsTourOpen(true)}
                            title="Обучение по виртуальному классу"
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                width: '30px',
                                height: '30px',
                                borderRadius: '10px',
                                border: 'none',
                                cursor: 'pointer',
                                backgroundColor: isLight ? 'rgba(0, 0, 0, 0.04)' : 'rgba(255, 255, 255, 0.08)',
                                color: isLight ? '#7D39EB' : '#C6FF33'
                            }}>
                            <HelpCircle size={15} />
                        </button>

                        {/* Toggle Video Visibility */}
                        <button
                            type="button"
                            onClick={() => setIsVideoHidden(prev => !prev)}
                            title={isVideoHidden ? 'Показать видео' : 'Скрыть видео'}
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                width: '30px',
                                height: '30px',
                                borderRadius: '10px',
                                border: 'none',
                                cursor: 'pointer',
                                backgroundColor: !isVideoHidden
                                    ? (isLight ? 'rgba(125, 57, 235, 0.12)' : 'rgba(125, 57, 235, 0.3)')
                                    : (isLight ? 'rgba(0, 0, 0, 0.04)' : 'rgba(255, 255, 255, 0.08)'),
                                color: !isVideoHidden ? '#7D39EB' : (isLight ? '#64748B' : '#94A3B8')
                            }}>
                            <Video size={15} />
                        </button>
                    </div>
                </header>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* 2.5 FLOATING EXCALIDRAW DRAWING TOOLBAR ISLAND      */}
            {/* ═══════════════════════════════════════════════════ */}
            {!isVideoFullscreen && !isBoardLocked && (
                <div style={{
                    position: 'absolute',
                    top: isMobile ? 'calc(max(8px, env(safe-area-inset-top)) + 52px)' : '16px',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    maxWidth: isMobile ? 'calc(100vw - 16px)' : 'none',
                    overflowX: 'auto',
                    scrollbarWidth: 'none',
                    display: 'flex',
                    alignItems: 'center',
                    gap: isMobile ? '3px' : '4px',
                    padding: isMobile ? '4px 6px' : '5px 8px',
                    borderRadius: '16px',
                    backgroundColor: isLight ? 'rgba(255, 255, 255, 0.94)' : 'rgba(18, 18, 20, 0.92)',
                    backdropFilter: 'blur(20px) saturate(160%)',
                    WebkitBackdropFilter: 'blur(20px) saturate(160%)',
                    border: isLight ? '1px solid rgba(0, 0, 0, 0.08)' : '1px solid rgba(255, 255, 255, 0.15)',
                    boxShadow: isLight ? '0 8px 24px rgba(0, 0, 0, 0.08)' : '0 12px 32px rgba(0, 0, 0, 0.6)',
                    zIndex: 42,
                }}>
                    {[
                        { id: 'selection', title: 'Выделение', icon: MousePointer },
                        { id: 'freedraw', title: 'Карандаш / Ручка', icon: Pencil },
                        { id: 'rectangle', title: 'Прямоугольник', icon: Square },
                        { id: 'ellipse', title: 'Круг', icon: Circle },
                        { id: 'arrow', title: 'Стрелка', icon: ArrowUpRight },
                        { id: 'line', title: 'Линия', icon: Minus },
                        { id: 'text', title: 'Текст', icon: Type },
                        { id: 'eraser', title: 'Ластик', icon: Eraser },
                    ].map(tool => {
                        const Icon = tool.icon;
                        const isActive = activeTool === tool.id;
                        return (
                            <button
                                key={tool.id}
                                type="button"
                                onClick={() => selectTool(tool.id)}
                                title={tool.title}
                                style={{
                                    width: isMobile ? '34px' : '36px',
                                    height: isMobile ? '34px' : '36px',
                                    borderRadius: '10px',
                                    border: 'none',
                                    backgroundColor: isActive 
                                        ? '#7D39EB' 
                                        : 'transparent',
                                    color: isActive 
                                        ? '#FFFFFF' 
                                        : (isLight ? '#334155' : '#E2E8F0'),
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    cursor: 'pointer',
                                    transition: 'all 0.15s ease',
                                    boxShadow: isActive ? '0 2px 8px rgba(125, 57, 235, 0.4)' : 'none',
                                    flexShrink: 0
                                }}>
                                <Icon size={isMobile ? 16 : 18} />
                            </button>
                        );
                    })}

                    <div style={{
                        width: '1px',
                        height: '20px',
                        backgroundColor: isLight ? 'rgba(0, 0, 0, 0.08)' : 'rgba(255, 255, 255, 0.12)',
                        margin: '0 2px',
                        flexShrink: 0
                    }} />

                    {/* Undo button */}
                    <button
                        type="button"
                        onClick={handleUndo}
                        title="Отменить"
                        style={{
                            width: isMobile ? '34px' : '36px',
                            height: isMobile ? '34px' : '36px',
                            borderRadius: '10px',
                            border: 'none',
                            backgroundColor: 'transparent',
                            color: isLight ? '#64748B' : '#94A3B8',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'pointer',
                            flexShrink: 0
                        }}>
                        <Undo2 size={isMobile ? 16 : 18} />
                    </button>

                    {/* Redo button */}
                    <button
                        type="button"
                        onClick={handleRedo}
                        title="Повторить"
                        style={{
                            width: isMobile ? '34px' : '36px',
                            height: isMobile ? '34px' : '36px',
                            borderRadius: '10px',
                            border: 'none',
                            backgroundColor: 'transparent',
                            color: isLight ? '#64748B' : '#94A3B8',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'pointer',
                            flexShrink: 0
                        }}>
                        <Redo2 size={isMobile ? 16 : 18} />
                    </button>
                </div>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* 3. FLOATING DRAGGABLE & SCALABLE VIDEO ISLAND (PiP) */}
            {/* ═══════════════════════════════════════════════════ */}
            {!isVideoFullscreen && !isVideoHidden && (
                <div 
                    ref={videoIslandRef}
                    style={{
                        position: 'absolute',
                        ...(videoPos.x !== null ? {
                            left: `${videoPos.x}px`,
                            top: `${videoPos.y}px`,
                        } : (isMobile ? {
                            bottom: 'calc(env(safe-area-inset-bottom, 0px) + 82px)',
                            right: '12px',
                        } : {
                            top: '16px',
                            right: '16px',
                        })),
                        width: `${getVideoWidth()}px`,
                        borderRadius: '18px',
                        backgroundColor: isLight ? 'rgba(255, 255, 255, 0.94)' : 'rgba(18, 18, 20, 0.94)',
                        backdropFilter: 'blur(24px) saturate(160%)',
                        WebkitBackdropFilter: 'blur(24px) saturate(160%)',
                        border: isDraggingVideo 
                            ? '1.5px solid #7D39EB' 
                            : (isLight ? '1px solid rgba(0, 0, 0, 0.08)' : '1px solid rgba(255, 255, 255, 0.15)'),
                        boxShadow: isDraggingVideo
                            ? '0 16px 40px rgba(125, 57, 235, 0.35)'
                            : (isLight ? '0 12px 36px rgba(0, 0, 0, 0.12)' : '0 20px 48px rgba(0, 0, 0, 0.7)'),
                        zIndex: isDraggingVideo ? 50 : 38,
                        overflow: 'hidden',
                        display: 'flex',
                        flexDirection: 'column',
                        transition: isDraggingVideo ? 'none' : 'box-shadow 0.2s, border-color 0.2s, width 0.2s ease',
                    }}>
                    {/* Draggable Header with Move Handle & Scale Presets */}
                    <div 
                        onMouseDown={handleDragStart}
                        onTouchStart={handleDragStart}
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            padding: isMobile ? '4px 6px' : '6px 10px',
                            borderBottom: isLight ? '1px solid rgba(0, 0, 0, 0.06)' : '1px solid rgba(255, 255, 255, 0.08)',
                            fontSize: isMobile ? '10px' : '11px',
                            fontWeight: 700,
                            color: isLight ? '#64748B' : '#94A3B8',
                            cursor: isDraggingVideo ? 'grabbing' : 'grab',
                            userSelect: 'none',
                            touchAction: 'none',
                            backgroundColor: isLight ? 'rgba(241, 245, 249, 0.9)' : 'rgba(30, 41, 59, 0.9)'
                        }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '4px', minWidth: 0, pointerEvents: 'none' }}>
                            <Move size={isMobile ? 12 : 13} color={isLight ? '#475569' : '#94A3B8'} />
                            <span style={{
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                maxWidth: isMobile ? '55px' : '90px'
                            }}>
                                Видео
                            </span>
                        </div>

                        <div style={{ display: 'flex', alignItems: 'center', gap: '3px' }}>
                            {/* Scale Preset Button */}
                            <button
                                type="button"
                                onClick={(e) => { e.stopPropagation(); cycleVideoScale(); }}
                                title={`Масштаб: ${videoScale.toUpperCase()} (нажмите для переключения)`}
                                style={{
                                    padding: '2px 5px',
                                    borderRadius: '6px',
                                    border: isLight ? '1px solid rgba(0, 0, 0, 0.1)' : '1px solid rgba(255, 255, 255, 0.15)',
                                    backgroundColor: isLight ? '#FFFFFF' : 'rgba(255, 255, 255, 0.1)',
                                    color: '#7D39EB',
                                    fontSize: '9px',
                                    fontWeight: 800,
                                    cursor: 'pointer',
                                    lineHeight: 1
                                }}>
                                {videoScale.toUpperCase()}
                            </button>

                            {/* Maximize to FaceTime split view */}
                            <button
                                type="button"
                                onClick={(e) => { e.stopPropagation(); setIsVideoFullscreen(true); }}
                                title="Развернуть видеозвонок на весь экран"
                                style={{
                                    border: 'none',
                                    background: 'transparent',
                                    cursor: 'pointer',
                                    color: isLight ? '#64748B' : '#94A3B8',
                                    padding: '2px',
                                    display: 'flex',
                                    alignItems: 'center'
                                }}>
                                <Maximize2 size={isMobile ? 12 : 14} />
                            </button>
                        </div>
                    </div>

                    {/* Video Feeds Grid */}
                    <div style={{
                        display: 'grid',
                        gridTemplateColumns: '1fr 1fr',
                        gap: '4px',
                        padding: '4px'
                    }}>
                        {/* 1. Local Video Tile */}
                        <div style={{
                            position: 'relative',
                            aspectRatio: '4/3',
                            borderRadius: '12px',
                            backgroundColor: isLight ? '#F1F5F9' : '#0F172A',
                            overflow: 'hidden',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }}>
                            <video 
                                ref={localVideoRef} 
                                autoPlay 
                                playsInline 
                                muted 
                                style={{ 
                                    width: '100%', 
                                    height: '100%', 
                                    objectFit: 'cover', 
                                    transform: cameraFacing === 'user' ? 'scaleX(-1)' : 'none',
                                    display: isCameraOn ? 'block' : 'none' 
                                }} 
                            />
                            {!isCameraOn && (
                                <div style={{
                                    width: isMobile ? '28px' : '40px',
                                    height: isMobile ? '28px' : '40px',
                                    borderRadius: '50%',
                                    backgroundColor: '#7D39EB',
                                    color: '#FFFFFF',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    fontWeight: 700,
                                    fontSize: isMobile ? '12px' : '15px'
                                }}>
                                    {config.userName ? config.userName.charAt(0).toUpperCase() : 'Я'}
                                </div>
                            )}
                            <div style={{
                                position: 'absolute',
                                bottom: '4px',
                                left: '4px',
                                display: 'flex',
                                alignItems: 'center',
                                gap: '3px',
                                padding: '2px 5px',
                                borderRadius: '6px',
                                backgroundColor: 'rgba(0, 0, 0, 0.6)',
                                color: '#FFFFFF',
                                fontSize: '9px',
                                fontWeight: 600
                            }}>
                                <span>Вы</span>
                                {!isMicOn && <MicOff size={10} color="#F87171" />}
                            </div>
                        </div>

                        {/* 2. Remote Partner Tile */}
                        <div style={{
                            position: 'relative',
                            aspectRatio: '4/3',
                            borderRadius: '12px',
                            backgroundColor: isLight ? '#F1F5F9' : '#0F172A',
                            overflow: 'hidden',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }}>
                            <video 
                                ref={remoteVideoRef} 
                                autoPlay 
                                playsInline 
                                style={{ width: '100%', height: '100%', objectFit: 'cover', display: partnerVideoOn ? 'block' : 'none' }} 
                            />
                            {!partnerVideoOn && (
                                <div style={{
                                    width: isMobile ? '28px' : '40px',
                                    height: isMobile ? '28px' : '40px',
                                    borderRadius: '50%',
                                    backgroundColor: '#3B82F6',
                                    color: '#FFFFFF',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    fontWeight: 700,
                                    fontSize: isMobile ? '12px' : '15px'
                                }}>
                                    {config.partnerName ? config.partnerName.charAt(0).toUpperCase() : 'У'}
                                </div>
                            )}
                            <div style={{
                                position: 'absolute',
                                bottom: '4px',
                                left: '4px',
                                display: 'flex',
                                alignItems: 'center',
                                gap: '3px',
                                padding: '2px 5px',
                                borderRadius: '6px',
                                backgroundColor: 'rgba(0, 0, 0, 0.6)',
                                color: '#FFFFFF',
                                fontSize: '9px',
                                fontWeight: 600
                            }}>
                                <span>{config.partnerName ? config.partnerName.split(' ')[0] : 'Собеседник'}</span>
                            </div>
                            {!isMobile && !partnerConnected && (
                                <button
                                    type="button"
                                    onClick={copyInviteLink}
                                    style={{
                                        position: 'absolute',
                                        bottom: '4px',
                                        right: '4px',
                                        padding: '2px 6px',
                                        borderRadius: '6px',
                                        border: 'none',
                                        backgroundColor: linkCopied ? '#10B981' : 'rgba(125, 57, 235, 0.85)',
                                        color: '#FFFFFF',
                                        fontSize: '9px',
                                        fontWeight: 600,
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '3px',
                                        cursor: 'pointer'
                                    }}>
                                    {linkCopied ? <Check size={10} /> : <Copy size={10} />}
                                    <span>{linkCopied ? 'Скопировано' : 'Ссылка'}</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* 4. FLOATING BOTTOM DOCK                            */}
            {/* ═══════════════════════════════════════════════════ */}
            {!isVideoFullscreen && (
                <nav style={{
                    position: 'absolute',
                    bottom: isMobile ? 'calc(env(safe-area-inset-bottom, 0px) + 20px)' : '16px',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    width: isMobile ? 'min(358px, calc(100vw - 16px))' : 'auto',
                    maxWidth: isMobile ? '358px' : 'none',
                    height: isMobile ? '56px' : '58px',
                    padding: isMobile ? '4px 10px' : '4px 16px',
                    borderRadius: isMobile ? '24px' : '9999px',
                    backgroundColor: isLight ? 'rgba(255, 255, 255, 0.95)' : 'rgba(18, 18, 20, 0.92)',
                    backdropFilter: 'blur(28px) saturate(180%)',
                    WebkitBackdropFilter: 'blur(28px) saturate(180%)',
                    border: isLight ? '1px solid rgba(0, 0, 0, 0.08)' : '1px solid rgba(255, 255, 255, 0.16)',
                    boxShadow: isLight ? '0 12px 36px rgba(0, 0, 0, 0.14)' : '0 20px 48px rgba(0, 0, 0, 0.7)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    gap: isMobile ? '4px' : '8px',
                    zIndex: 55,
                    boxSizing: 'border-box'
                }}>
                    {/* 1. Mic Toggle */}
                    <button
                        type="button"
                        onClick={toggleMic}
                        title={isMicOn ? 'Выключить микрофон' : 'Включить микрофон'}
                        style={dockButtonStyle(isLight, isMicOn ? (isLight ? 'rgba(0, 0, 0, 0.04)' : 'rgba(255, 255, 255, 0.1)') : 'rgba(239, 68, 68, 0.15)', isMobile)}>
                        {isMicOn ? <Mic size={isMobile ? 18 : 20} color={isLight ? '#0F172A' : '#FFFFFF'} /> : <MicOff size={isMobile ? 18 : 20} color="#EF4444" />}
                        <span style={dockLabelStyle(isLight)}>{isMicOn ? 'Микро' : 'Выкл'}</span>
                    </button>

                    {/* 2. Camera Toggle */}
                    <button
                        type="button"
                        onClick={toggleCamera}
                        title={isCameraOn ? 'Выключить камеру' : 'Включить камеру'}
                        style={dockButtonStyle(isLight, isCameraOn ? (isLight ? 'rgba(0, 0, 0, 0.04)' : 'rgba(255, 255, 255, 0.1)') : 'rgba(239, 68, 68, 0.15)', isMobile)}>
                        {isCameraOn ? <Video size={isMobile ? 18 : 20} color={isLight ? '#0F172A' : '#FFFFFF'} /> : <VideoOff size={isMobile ? 18 : 20} color="#EF4444" />}
                        <span style={dockLabelStyle(isLight)}>{isCameraOn ? 'Камера' : 'Выкл'}</span>
                    </button>

                    {/* Desktop Only: Undo & Redo */}
                    {!isMobile && (
                        <>
                            <button
                                type="button"
                                onClick={handleUndo}
                                title="Отменить последнее действие"
                                style={dockButtonStyle(isLight, 'transparent', isMobile)}>
                                <Undo2 size={20} color={isLight ? '#334155' : '#E2E8F0'} />
                                <span style={dockLabelStyle(isLight)}>Назад</span>
                            </button>
                            <button
                                type="button"
                                onClick={handleRedo}
                                title="Повторить действие"
                                style={dockButtonStyle(isLight, 'transparent', isMobile)}>
                                <Redo2 size={20} color={isLight ? '#334155' : '#E2E8F0'} />
                                <span style={dockLabelStyle(isLight)}>Вперёд</span>
                            </button>
                        </>
                    )}

                    {/* 3. Call Toggle (Primary Lime Accent Button) */}
                    <button
                        type="button"
                        onClick={() => {
                            setIsVideoFullscreen(prev => !prev);
                            if (!isCameraOn) toggleCamera();
                        }}
                        title={isVideoFullscreen ? 'Вернуться к доске' : 'Развернуть видеозвонок'}
                        style={{
                            ...dockButtonStyle(isLight, '#84cc16', isMobile),
                            backgroundColor: isLight ? '#84cc16' : '#C6FF33',
                            color: '#000000',
                            border: '1px solid rgba(255, 255, 255, 0.5)',
                            boxShadow: isLight ? '0 4px 14px rgba(132, 204, 22, 0.4)' : '0 4px 18px rgba(198, 255, 51, 0.5)'
                        }}>
                        <PhoneCall size={isMobile ? 18 : 20} color="#000000" />
                        <span style={{ ...dockLabelStyle(isLight), color: '#000000', fontWeight: 800 }}>Звонок</span>
                    </button>

                    {/* 4. Chat Drawer Button */}
                    <button
                        type="button"
                        onClick={() => setActiveTab(prev => prev === 'chat' ? null : 'chat')}
                        title="Открыть чат урока"
                        style={dockButtonStyle(isLight, activeTab === 'chat' ? (isLight ? '#E0E7FF' : 'rgba(125, 57, 235, 0.3)') : 'transparent', isMobile)}>
                        <div style={{ position: 'relative' }}>
                            <MessageSquare size={isMobile ? 18 : 20} color={activeTab === 'chat' ? '#7D39EB' : (isLight ? '#334155' : '#E2E8F0')} />
                            {unreadChatCount > 0 && (
                                <span style={{
                                    position: 'absolute',
                                    top: '-4px',
                                    right: '-6px',
                                    width: '14px',
                                    height: '14px',
                                    borderRadius: '50%',
                                    backgroundColor: '#7D39EB',
                                    color: '#FFFFFF',
                                    fontSize: '8px',
                                    fontWeight: 700,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center'
                                }}>
                                    {unreadChatCount}
                                </span>
                            )}
                        </div>
                        <span style={dockLabelStyle(isLight)}>Чат</span>
                    </button>

                    {/* Desktop Only: Notes & AI */}
                    {!isMobile && (
                        <>
                            <button
                                type="button"
                                onClick={() => setActiveTab(prev => prev === 'notes' ? null : 'notes')}
                                title="Заметки урока"
                                style={dockButtonStyle(isLight, activeTab === 'notes' ? (isLight ? '#E0E7FF' : 'rgba(125, 57, 235, 0.3)') : 'transparent', isMobile)}>
                                <FileText size={20} color={activeTab === 'notes' ? '#7D39EB' : (isLight ? '#334155' : '#E2E8F0')} />
                                <span style={dockLabelStyle(isLight)}>Заметки</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab(prev => prev === 'ai' ? null : 'ai')}
                                title="ИИ-помощник и задания ЦТ"
                                style={dockButtonStyle(isLight, activeTab === 'ai' ? (isLight ? '#E0E7FF' : 'rgba(125, 57, 235, 0.3)') : 'transparent', isMobile)}>
                                <Sparkles size={20} color={activeTab === 'ai' ? '#7D39EB' : (isLight ? '#334155' : '#E2E8F0')} />
                                <span style={dockLabelStyle(isLight)}>ИИ / ЦТ</span>
                            </button>
                            <button
                                type="button"
                                onClick={toggleTheme}
                                title={isLight ? 'Переключить на тёмную тему' : 'Переключить на светлую тему'}
                                style={dockButtonStyle(isLight, 'transparent', isMobile)}>
                                {isLight ? <Moon size={20} color="#334155" /> : <Sun size={20} color="#FCD34D" />}
                                <span style={dockLabelStyle(isLight)}>Тема</span>
                            </button>
                        </>
                    )}

                    {/* Mobile Only: More Actions Sheet */}
                    {isMobile && (
                        <button
                            type="button"
                            onClick={() => setMobileMoreOpen(true)}
                            title="Все действия и материалы"
                            style={dockButtonStyle(isLight, mobileMoreOpen ? (isLight ? '#E0E7FF' : 'rgba(125, 57, 235, 0.3)') : 'transparent', isMobile)}>
                            <MoreHorizontal size={18} color={isLight ? '#334155' : '#E2E8F0'} />
                            <span style={dockLabelStyle(isLight)}>Ещё</span>
                        </button>
                    )}

                    {/* Exit Button */}
                    <button
                        type="button"
                        onClick={() => setExitModalOpen(true)}
                        title={config.userRole === 'tutor' ? 'Завершить урок для всех' : 'Выйти из урока'}
                        style={dockButtonStyle(isLight, 'rgba(239, 68, 68, 0.1)', isMobile)}>
                        <LogOut size={isMobile ? 18 : 20} color="#EF4444" />
                        <span style={{ ...dockLabelStyle(isLight), color: '#EF4444' }}>Выход</span>
                    </button>
                </nav>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* 5. SLIDE-OUT DRAWER / MOBILE BOTTOM SHEET          */}
            {/* ═══════════════════════════════════════════════════ */}
            {activeTab && (
                <>
                    {/* Backdrop for Mobile */}
                    {isMobile && (
                        <div 
                            onClick={() => setActiveTab(null)}
                            style={{
                                position: 'fixed',
                                inset: 0,
                                backgroundColor: 'rgba(0, 0, 0, 0.5)',
                                backdropFilter: 'blur(4px)',
                                zIndex: isVideoFullscreen ? 140 : 60
                            }}
                        />
                    )}

                    <aside style={{
                        position: 'fixed',
                        ...(isMobile ? {
                            bottom: 0,
                            left: 0,
                            right: 0,
                            maxHeight: '82vh',
                            borderRadius: '24px 24px 0 0',
                            borderTop: isLight ? '1px solid rgba(0, 0, 0, 0.08)' : '1px solid rgba(255, 255, 255, 0.15)',
                        } : {
                            top: 0,
                            right: 0,
                            bottom: 0,
                            width: '380px',
                            borderLeft: isLight ? '1px solid rgba(0, 0, 0, 0.08)' : '1px solid rgba(255, 255, 255, 0.15)',
                        }),
                        backgroundColor: isLight ? 'rgba(255, 255, 255, 0.95)' : 'rgba(18, 18, 20, 0.95)',
                        backdropFilter: 'blur(28px)',
                        WebkitBackdropFilter: 'blur(28px)',
                        boxShadow: isLight ? '0 -10px 40px rgba(0, 0, 0, 0.12)' : '0 -15px 50px rgba(0, 0, 0, 0.7)',
                        zIndex: isVideoFullscreen ? 150 : 70,
                        display: 'flex',
                        flexDirection: 'column',
                        overflow: 'hidden'
                    }}>
                        {/* Pull handle on mobile */}
                        {isMobile && (
                            <div 
                                onClick={() => setActiveTab(null)}
                                style={{
                                    width: '36px',
                                    height: '4px',
                                    borderRadius: '2px',
                                    backgroundColor: isLight ? 'rgba(0, 0, 0, 0.2)' : 'rgba(255, 255, 255, 0.3)',
                                    margin: '8px auto 4px auto',
                                    cursor: 'pointer'
                                }}
                            />
                        )}

                        {/* Drawer Header Tabs */}
                        <div style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            padding: '10px 14px',
                            borderBottom: isLight ? '1px solid rgba(0, 0, 0, 0.06)' : '1px solid rgba(255, 255, 255, 0.08)'
                        }}>
                            <div style={{ display: 'flex', gap: '4px', overflowX: 'auto', scrollbarWidth: 'none' }}>
                                {[
                                    { key: 'chat', label: 'Чат', icon: MessageSquare },
                                    { key: 'notes', label: 'Заметки', icon: FileText },
                                    { key: 'files', label: 'Файлы', icon: Folder },
                                    { key: 'ai', label: 'ИИ-помощник', icon: Sparkles }
                                ].map(t => (
                                    <button
                                        key={t.key}
                                        type="button"
                                        onClick={() => setActiveTab(t.key)}
                                        style={{
                                            padding: '6px 12px',
                                            borderRadius: '8px',
                                            border: 'none',
                                            cursor: 'pointer',
                                            fontSize: '12px',
                                            fontWeight: 600,
                                            backgroundColor: activeTab === t.key 
                                                ? (isLight ? '#7D39EB' : '#7D39EB') 
                                                : 'transparent',
                                            color: activeTab === t.key 
                                                ? '#FFFFFF' 
                                                : (isLight ? '#64748B' : '#94A3B8'),
                                            whiteSpace: 'nowrap'
                                        }}>
                                        {t.label}
                                    </button>
                                ))}
                            </div>
                            <button
                                type="button"
                                onClick={() => setActiveTab(null)}
                                title="Закрыть"
                                style={{
                                    border: 'none',
                                    background: 'transparent',
                                    cursor: 'pointer',
                                    color: isLight ? '#64748B' : '#94A3B8',
                                    padding: '4px'
                                }}>
                                <X size={18} />
                            </button>
                        </div>

                        {/* Drawer Body */}
                        <div style={{ flex: 1, overflowY: 'auto', padding: '14px', display: 'flex', flexDirection: 'column' }}>
                            {activeTab === 'chat' && (
                                <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
                                    <div style={{ flex: 1, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                                        {chatMessages.length === 0 ? (
                                            <div style={{ textAlign: 'center', margin: 'auto', color: isLight ? '#94A3B8' : '#64748B', fontSize: '13px' }}>
                                                Пока сообщений нет. Напишите первое сообщение!
                                            </div>
                                        ) : (
                                            chatMessages.map(m => {
                                                const isMe = m.sender_id === config.userId;
                                                return (
                                                    <div key={m.id} style={{
                                                        alignSelf: isMe ? 'flex-end' : 'flex-start',
                                                        maxWidth: '85%',
                                                        padding: '8px 12px',
                                                        borderRadius: '14px',
                                                        backgroundColor: isMe ? '#7D39EB' : (isLight ? '#F1F5F9' : '#1E293B'),
                                                        color: isMe ? '#FFFFFF' : (isLight ? '#0F172A' : '#FFFFFF'),
                                                        fontSize: '13px'
                                                    }}>
                                                        <div>{m.message}</div>
                                                        <div style={{ fontSize: '10px', opacity: 0.7, textAlign: 'right', marginTop: '2px' }}>
                                                            {m.created_at}
                                                        </div>
                                                    </div>
                                                );
                                            })
                                        )}
                                    </div>
                                    <form onSubmit={sendMessage} style={{ display: 'flex', gap: '8px', marginTop: '12px' }}>
                                        <input
                                            type="text"
                                            value={messageInput}
                                            onChange={e => setMessageInput(e.target.value)}
                                            placeholder="Сообщение..."
                                            style={{
                                                flex: 1,
                                                padding: '10px 14px',
                                                borderRadius: '12px',
                                                border: isLight ? '1px solid rgba(0, 0, 0, 0.12)' : '1px solid rgba(255, 255, 255, 0.15)',
                                                backgroundColor: isLight ? '#FFFFFF' : 'rgba(255, 255, 255, 0.05)',
                                                color: isLight ? '#0F172A' : '#FFFFFF',
                                                fontSize: '13px',
                                                outline: 'none'
                                            }}
                                        />
                                        <button
                                            type="submit"
                                            style={{
                                                padding: '10px 14px',
                                                borderRadius: '12px',
                                                border: 'none',
                                                backgroundColor: '#7D39EB',
                                                color: '#FFFFFF',
                                                cursor: 'pointer',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center'
                                            }}>
                                            <Send size={16} />
                                        </button>
                                    </form>
                                </div>
                            )}

                            {activeTab === 'ai' && (
                                <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
                                    <div style={{ flex: 1, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '10px' }}>
                                        {aiMessages.map((m, idx) => (
                                            <div key={idx} style={{
                                                padding: '10px 14px',
                                                borderRadius: '14px',
                                                backgroundColor: m.role === 'user' 
                                                    ? '#7D39EB' 
                                                    : (isLight ? '#F1F5F9' : '#1E293B'),
                                                color: m.role === 'user' ? '#FFFFFF' : (isLight ? '#0F172A' : '#FFFFFF'),
                                                alignSelf: m.role === 'user' ? 'flex-end' : 'flex-start',
                                                maxWidth: '90%',
                                                fontSize: '13px',
                                                lineHeight: 1.5
                                            }}>
                                                {m.text}
                                            </div>
                                        ))}
                                        {isAiLoading && (
                                            <div style={{ fontSize: '12px', color: '#7D39EB', display: 'flex', alignItems: 'center', gap: '6px' }}>
                                                <RefreshCw size={14} className="animate-spin" />
                                                <span>ИИ думает...</span>
                                            </div>
                                        )}
                                    </div>
                                    <form onSubmit={sendAiMessage} style={{ display: 'flex', gap: '8px', marginTop: '12px' }}>
                                        <input
                                            type="text"
                                            value={aiInput}
                                            onChange={e => setAiInput(e.target.value)}
                                            placeholder="Спросить у ИИ..."
                                            style={{
                                                flex: 1,
                                                padding: '10px 14px',
                                                borderRadius: '12px',
                                                border: isLight ? '1px solid rgba(0, 0, 0, 0.12)' : '1px solid rgba(255, 255, 255, 0.15)',
                                                backgroundColor: isLight ? '#FFFFFF' : 'rgba(255, 255, 255, 0.05)',
                                                color: isLight ? '#0F172A' : '#FFFFFF',
                                                fontSize: '13px',
                                                outline: 'none'
                                            }}
                                        />
                                        <button
                                            type="submit"
                                            disabled={isAiLoading}
                                            style={{
                                                padding: '10px 14px',
                                                borderRadius: '12px',
                                                border: 'none',
                                                backgroundColor: '#7D39EB',
                                                color: '#FFFFFF',
                                                cursor: isAiLoading ? 'not-allowed' : 'pointer'
                                            }}>
                                            <Sparkles size={16} />
                                        </button>
                                    </form>
                                </div>
                            )}

                            {activeTab === 'notes' && (
                                <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
                                    <div style={{ flex: 1, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                                        {notesLoading ? (
                                            <div style={{ textAlign: 'center', padding: '24px', color: '#7D39EB', fontSize: '13px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px' }}>
                                                <RefreshCw size={14} className="animate-spin" />
                                                <span>Загрузка заметок...</span>
                                            </div>
                                        ) : notes.length === 0 ? (
                                            <div style={{ textAlign: 'center', margin: 'auto', color: isLight ? '#94A3B8' : '#64748B', fontSize: '13px' }}>
                                                Заметок пока нет. Запишите формулу или тезис урока!
                                            </div>
                                        ) : (
                                            notes.map(n => (
                                                <div key={n.id} style={{
                                                    padding: '10px 12px',
                                                    borderRadius: '12px',
                                                    backgroundColor: isLight ? '#F8FAFC' : 'rgba(255, 255, 255, 0.05)',
                                                    border: isLight ? '1px solid rgba(0, 0, 0, 0.06)' : '1px solid rgba(255, 255, 255, 0.08)',
                                                    color: isLight ? '#0F172A' : '#FFFFFF',
                                                    fontSize: '13px'
                                                }}>
                                                    <div style={{ whiteSpace: 'pre-wrap', lineHeight: 1.4 }}>{n.text}</div>
                                                    <div style={{ fontSize: '10px', color: isLight ? '#94A3B8' : '#64748B', marginTop: '4px', textAlign: 'right' }}>
                                                        {n.time}
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                    <form onSubmit={addNote} style={{ display: 'flex', gap: '8px', marginTop: '12px' }}>
                                        <input
                                            type="text"
                                            value={noteInput}
                                            onChange={e => setNoteInput(e.target.value)}
                                            placeholder="Новая заметка к уроку..."
                                            style={{
                                                flex: 1,
                                                padding: '10px 14px',
                                                borderRadius: '12px',
                                                border: isLight ? '1px solid rgba(0, 0, 0, 0.12)' : '1px solid rgba(255, 255, 255, 0.15)',
                                                backgroundColor: isLight ? '#FFFFFF' : 'rgba(255, 255, 255, 0.05)',
                                                color: isLight ? '#0F172A' : '#FFFFFF',
                                                fontSize: '13px',
                                                outline: 'none'
                                            }}
                                        />
                                        <button
                                            type="submit"
                                            style={{
                                                padding: '10px 14px',
                                                borderRadius: '12px',
                                                border: 'none',
                                                backgroundColor: '#7D39EB',
                                                color: '#FFFFFF',
                                                cursor: 'pointer',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center'
                                            }}>
                                            <Plus size={16} />
                                        </button>
                                    </form>
                                </div>
                            )}

                            {activeTab === 'files' && (
                                <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
                                    <div style={{ flex: 1, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                                        {filesLoading ? (
                                            <div style={{ textAlign: 'center', padding: '24px', color: '#7D39EB', fontSize: '13px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px' }}>
                                                <RefreshCw size={14} className="animate-spin" />
                                                <span>Загрузка файлов...</span>
                                            </div>
                                        ) : files.length === 0 ? (
                                            <div style={{ textAlign: 'center', margin: 'auto', color: isLight ? '#94A3B8' : '#64748B', fontSize: '13px' }}>
                                                К уроку пока не прикреплено файлов. Загрузите PDF, DOC или изображение!
                                            </div>
                                        ) : (
                                            files.map(f => (
                                                <div key={f.id} style={{
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'space-between',
                                                    padding: '10px 12px',
                                                    borderRadius: '12px',
                                                    backgroundColor: isLight ? '#F8FAFC' : 'rgba(255, 255, 255, 0.05)',
                                                    border: isLight ? '1px solid rgba(0, 0, 0, 0.06)' : '1px solid rgba(255, 255, 255, 0.08)',
                                                    gap: '8px'
                                                }}>
                                                    <div style={{ minWidth: 0, flex: 1 }}>
                                                        <div style={{
                                                            fontSize: '13px',
                                                            fontWeight: 600,
                                                            color: isLight ? '#0F172A' : '#FFFFFF',
                                                            whiteSpace: 'nowrap',
                                                            overflow: 'hidden',
                                                            textOverflow: 'ellipsis'
                                                        }}>
                                                            {f.name}
                                                        </div>
                                                        <div style={{ fontSize: '11px', color: isLight ? '#94A3B8' : '#64748B' }}>
                                                            {f.size}
                                                        </div>
                                                    </div>
                                                    <a
                                                        href={f.url}
                                                        download
                                                        title="Скачать файл"
                                                        style={{
                                                            padding: '8px',
                                                            borderRadius: '8px',
                                                            backgroundColor: isLight ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.1)',
                                                            color: isLight ? '#0F172A' : '#FFFFFF',
                                                            display: 'flex',
                                                            alignItems: 'center',
                                                            justifyContent: 'center',
                                                            textDecoration: 'none'
                                                        }}>
                                                        <Download size={16} />
                                                    </a>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                    <div style={{ marginTop: '12px' }}>
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            onChange={handleFileUpload}
                                            style={{ display: 'none' }}
                                            accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.gif"
                                        />
                                        <button
                                            type="button"
                                            disabled={isUploadingFile}
                                            onClick={() => fileInputRef.current?.click()}
                                            style={{
                                                width: '100%',
                                                padding: '10px',
                                                borderRadius: '12px',
                                                border: '1px dashed #7D39EB',
                                                backgroundColor: isLight ? 'rgba(125, 57, 235, 0.05)' : 'rgba(125, 57, 235, 0.15)',
                                                color: '#7D39EB',
                                                fontSize: '13px',
                                                fontWeight: 600,
                                                cursor: isUploadingFile ? 'not-allowed' : 'pointer',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                gap: '6px'
                                            }}>
                                            <Plus size={16} />
                                            <span>{isUploadingFile ? 'Загрузка...' : 'Загрузить файл к уроку'}</span>
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </aside>
                </>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* 6. MOBILE "MORE" ACTIONS BOTTOM SHEET              */}
            {/* ═══════════════════════════════════════════════════ */}
            {mobileMoreOpen && (
                <>
                    <div 
                        onClick={() => setMobileMoreOpen(false)}
                        style={{
                            position: 'fixed',
                            inset: 0,
                            backgroundColor: 'rgba(0, 0, 0, 0.5)',
                            backdropFilter: 'blur(4px)',
                            zIndex: 80
                        }}
                    />
                    <div style={{
                        position: 'fixed',
                        bottom: 0,
                        left: 0,
                        right: 0,
                        borderRadius: '24px 24px 0 0',
                        backgroundColor: isLight ? 'rgba(255, 255, 255, 0.98)' : 'rgba(18, 18, 20, 0.98)',
                        backdropFilter: 'blur(28px)',
                        padding: '12px 16px max(16px, env(safe-area-inset-bottom)) 16px',
                        zIndex: 90,
                        display: 'flex',
                        flexDirection: 'column',
                        gap: '8px'
                    }}>
                        <div style={{
                            width: '36px',
                            height: '4px',
                            borderRadius: '2px',
                            backgroundColor: isLight ? 'rgba(0, 0, 0, 0.2)' : 'rgba(255, 255, 255, 0.3)',
                            margin: '4px auto 10px auto'
                        }} />

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
                            <button
                                type="button"
                                onClick={() => { setMobileMoreOpen(false); setActiveTab('ai'); }}
                                style={sheetItemStyle(isLight)}>
                                <Sparkles size={18} color="#7D39EB" />
                                <span>ИИ Репетитор</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => { setMobileMoreOpen(false); setActiveTab('notes'); }}
                                style={sheetItemStyle(isLight)}>
                                <FileText size={18} color="#3B82F6" />
                                <span>Заметки</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => { setMobileMoreOpen(false); setActiveTab('files'); }}
                                style={sheetItemStyle(isLight)}>
                                <Folder size={18} color="#10B981" />
                                <span>Файлы</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => { setMobileMoreOpen(false); setIsTourOpen(true); }}
                                style={sheetItemStyle(isLight)}>
                                <HelpCircle size={18} color="#06B6D4" />
                                <span>Обучение</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => { setMobileMoreOpen(false); toggleTheme(); }}
                                style={sheetItemStyle(isLight)}>
                                {isLight ? <Moon size={18} color="#F59E0B" /> : <Sun size={18} color="#FCD34D" />}
                                <span>{isLight ? 'Тёмная тема' : 'Светлая тема'}</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => { handleUndo(); setMobileMoreOpen(false); }}
                                style={sheetItemStyle(isLight)}>
                                <Undo2 size={18} color="#64748B" />
                                <span>Шаг назад</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => { copyInviteLink(); setMobileMoreOpen(false); }}
                                style={sheetItemStyle(isLight)}>
                                <Copy size={18} color="#8B5CF6" />
                                <span>Ссылка на урок</span>
                            </button>
                        </div>

                        <button
                            type="button"
                            onClick={() => { setMobileMoreOpen(false); setExitModalOpen(true); }}
                            style={{
                                width: '100%',
                                marginTop: '6px',
                                padding: '12px',
                                borderRadius: '14px',
                                border: 'none',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                color: '#EF4444',
                                fontWeight: 700,
                                fontSize: '13px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '8px',
                                cursor: 'pointer'
                            }}>
                            <LogOut size={16} />
                            <span>{config.userRole === 'tutor' ? 'Завершить урок' : 'Выйти из урока'}</span>
                        </button>
                    </div>
                </>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* 7. REDESIGNED FULLSCREEN FACETIME VIDEO CALL MODAL  */}
            {/* ═══════════════════════════════════════════════════ */}
            {isVideoFullscreen && (
                <div style={{
                    position: 'fixed',
                    inset: 0,
                    width: '100vw',
                    height: '100dvh',
                    background: 'radial-gradient(ellipse at 50% 15%, #1e1b4b 0%, #0c0f1d 45%, #030712 100%)',
                    zIndex: 100,
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden'
                }}>
                    {/* Top Floating Glass Header */}
                    <div style={{
                        position: 'absolute',
                        top: 'max(12px, env(safe-area-inset-top))',
                        left: '12px',
                        right: '12px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: '8px',
                        zIndex: 110
                    }}>
                        {/* Back to Whiteboard Button */}
                        <button
                            type="button"
                            onClick={() => setIsVideoFullscreen(false)}
                            style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: '6px',
                                padding: '8px 14px',
                                borderRadius: '9999px',
                                backgroundColor: 'rgba(255, 255, 255, 0.15)',
                                backdropFilter: 'blur(24px) saturate(160%)',
                                WebkitBackdropFilter: 'blur(24px) saturate(160%)',
                                color: '#FFFFFF',
                                border: '1px solid rgba(255, 255, 255, 0.2)',
                                boxShadow: '0 8px 24px rgba(0, 0, 0, 0.4)',
                                cursor: 'pointer',
                                fontSize: '13px',
                                fontWeight: 600,
                                transition: 'all 0.15s ease'
                            }}>
                            <ChevronLeft size={18} />
                            <span>К доске</span>
                        </button>

                        {/* Live Call Indicator Pill */}
                        <div style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '8px',
                            padding: '6px 14px',
                            borderRadius: '9999px',
                            backgroundColor: 'rgba(0, 0, 0, 0.55)',
                            backdropFilter: 'blur(20px)',
                            WebkitBackdropFilter: 'blur(20px)',
                            border: '1px solid rgba(255, 255, 255, 0.12)',
                            boxShadow: '0 8px 24px rgba(0, 0, 0, 0.4)',
                            color: '#FFFFFF',
                            fontSize: '12px',
                            fontWeight: 600
                        }}>
                            <span style={{
                                width: '8px',
                                height: '8px',
                                borderRadius: '50%',
                                backgroundColor: partnerConnected ? '#10B981' : '#F59E0B',
                                boxShadow: partnerConnected ? '0 0 10px #10B981' : '0 0 10px #F59E0B'
                            }} />
                            <span style={{ fontFamily: 'monospace', fontSize: '13px', letterSpacing: '0.5px' }}>
                                {formatCallTime(callSeconds)}
                            </span>
                            <span style={{
                                fontSize: '10px',
                                fontWeight: 800,
                                color: '#C6FF33',
                                padding: '1px 5px',
                                borderRadius: '4px',
                                backgroundColor: 'rgba(198, 255, 51, 0.15)'
                            }}>
                                HD
                            </span>
                        </div>

                        {/* Layout Toggle (50/50 vs Focus PiP) */}
                        <button
                            type="button"
                            onClick={() => setCallLayout(prev => prev === 'split' ? 'focus' : 'split')}
                            title={callLayout === 'split' ? 'Переключить на полноэкранный фокус (PiP)' : 'Переключить на разделенный экран (50/50)'}
                            style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: '4px',
                                padding: '8px 12px',
                                borderRadius: '9999px',
                                backgroundColor: 'rgba(255, 255, 255, 0.15)',
                                backdropFilter: 'blur(24px)',
                                WebkitBackdropFilter: 'blur(24px)',
                                color: '#FFFFFF',
                                border: '1px solid rgba(255, 255, 255, 0.2)',
                                cursor: 'pointer',
                                fontSize: '12px',
                                fontWeight: 600
                            }}>
                            <LayoutGrid size={15} color="#C6FF33" />
                            <span style={{ display: isMobile ? 'none' : 'inline' }}>
                                {callLayout === 'split' ? 'Сетка' : 'Фокус'}
                            </span>
                        </button>
                    </div>

                    {/* Call Stage Area */}
                    {callLayout === 'split' ? (
                        /* ═══ 1. SPLIT VIEW (FaceTime 50/50) ═══ */
                        <div style={{
                            flex: 1,
                            display: 'grid',
                            gridTemplateRows: isMobile ? '1fr 1fr' : 'none',
                            gridTemplateColumns: isMobile ? 'none' : '1fr 1fr',
                            gap: isMobile ? '8px' : '14px',
                            padding: isMobile ? '60px 10px 84px 10px' : '68px 16px 88px 16px',
                            boxSizing: 'border-box',
                            height: '100%'
                        }}>
                            {/* 1. Remote Partner Tile */}
                            <div style={{
                                position: 'relative',
                                borderRadius: '24px',
                                backgroundColor: 'rgba(15, 23, 42, 0.75)',
                                backdropFilter: 'blur(16px)',
                                WebkitBackdropFilter: 'blur(16px)',
                                border: '1px solid rgba(255, 255, 255, 0.12)',
                                boxShadow: '0 16px 40px rgba(0, 0, 0, 0.5)',
                                overflow: 'hidden',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>
                                {/* Video element - always absolute fill */}
                                <video 
                                    ref={remoteFsVideoRef}
                                    autoPlay 
                                    playsInline 
                                    style={{
                                        position: 'absolute',
                                        inset: 0,
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'cover',
                                        display: partnerVideoOn ? 'block' : 'none'
                                    }} 
                                />

                                {/* Centered Placeholder when partner camera is OFF */}
                                {!partnerVideoOn && (
                                    <div style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        gap: '12px',
                                        zIndex: 2,
                                        padding: '16px',
                                        textAlign: 'center'
                                    }}>
                                        <div style={{
                                            width: isMobile ? '76px' : '96px',
                                            height: isMobile ? '76px' : '96px',
                                            borderRadius: '50%',
                                            background: 'linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%)',
                                            boxShadow: '0 0 40px rgba(59, 130, 246, 0.45)',
                                            color: '#FFFFFF',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontWeight: 800,
                                            fontSize: isMobile ? '28px' : '36px',
                                            border: '3px solid rgba(255, 255, 255, 0.2)'
                                        }}>
                                            {config.partnerName ? config.partnerName.charAt(0).toUpperCase() : 'У'}
                                        </div>
                                        <div style={{ display: 'flex', flexDirection: 'column', gap: '3px' }}>
                                            <span style={{ color: '#FFFFFF', fontSize: '15px', fontWeight: 700 }}>
                                                {config.partnerName || 'Собеседник'}
                                            </span>
                                            <span style={{ color: '#94A3B8', fontSize: '12px' }}>
                                                {partnerConnected ? 'Камера выключена' : 'Подключение...'}
                                            </span>
                                        </div>
                                    </div>
                                )}

                                {/* Partner Bottom Pill Tag */}
                                <div style={{
                                    position: 'absolute',
                                    bottom: '12px',
                                    left: '12px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '6px',
                                    padding: '5px 10px',
                                    borderRadius: '9999px',
                                    backgroundColor: 'rgba(0, 0, 0, 0.65)',
                                    backdropFilter: 'blur(12px)',
                                    color: '#FFFFFF',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    zIndex: 5
                                }}>
                                    <span>{config.partnerName || 'Собеседник'}</span>
                                    <div style={{
                                        width: '6px',
                                        height: '6px',
                                        borderRadius: '50%',
                                        backgroundColor: partnerConnected ? '#10B981' : '#F59E0B'
                                    }} />
                                </div>
                            </div>

                            {/* 2. Local Self Tile */}
                            <div style={{
                                position: 'relative',
                                borderRadius: '24px',
                                backgroundColor: 'rgba(15, 23, 42, 0.75)',
                                backdropFilter: 'blur(16px)',
                                WebkitBackdropFilter: 'blur(16px)',
                                border: '1px solid rgba(255, 255, 255, 0.12)',
                                boxShadow: '0 16px 40px rgba(0, 0, 0, 0.5)',
                                overflow: 'hidden',
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>
                                {/* Local mirrored video */}
                                <video 
                                    ref={localFsVideoRef}
                                    autoPlay 
                                    playsInline 
                                    muted 
                                    style={{
                                        position: 'absolute',
                                        inset: 0,
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'cover',
                                        transform: cameraFacing === 'user' ? 'scaleX(-1)' : 'none',
                                        display: isCameraOn ? 'block' : 'none'
                                    }} 
                                />

                                {/* Centered Placeholder when local camera is OFF */}
                                {!isCameraOn && (
                                    <div style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        gap: '12px',
                                        zIndex: 2,
                                        padding: '16px',
                                        textAlign: 'center'
                                    }}>
                                        <div style={{
                                            width: isMobile ? '76px' : '96px',
                                            height: isMobile ? '76px' : '96px',
                                            borderRadius: '50%',
                                            background: 'linear-gradient(135deg, #7D39EB 0%, #5B21B6 100%)',
                                            boxShadow: '0 0 40px rgba(125, 57, 235, 0.45)',
                                            color: '#FFFFFF',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontWeight: 800,
                                            fontSize: isMobile ? '28px' : '36px',
                                            border: '3px solid rgba(255, 255, 255, 0.2)'
                                        }}>
                                            {config.userName ? config.userName.charAt(0).toUpperCase() : 'Я'}
                                        </div>
                                        <div style={{ display: 'flex', flexDirection: 'column', gap: '3px' }}>
                                            <span style={{ color: '#FFFFFF', fontSize: '15px', fontWeight: 700 }}>
                                                {config.userName || 'Вы'}
                                            </span>
                                            <span style={{ color: '#94A3B8', fontSize: '12px' }}>
                                                Ваша камера выключена
                                            </span>
                                        </div>
                                    </div>
                                )}

                                {/* Local Bottom Pill Tag */}
                                <div style={{
                                    position: 'absolute',
                                    bottom: '12px',
                                    left: '12px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '6px',
                                    padding: '5px 10px',
                                    borderRadius: '9999px',
                                    backgroundColor: 'rgba(0, 0, 0, 0.65)',
                                    backdropFilter: 'blur(12px)',
                                    color: '#FFFFFF',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    zIndex: 5
                                }}>
                                    <span>Вы</span>
                                    {!isMicOn && (
                                        <span style={{ color: '#EF4444', fontSize: '10px', fontWeight: 700 }}>
                                            • Микрофон выкл
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ) : (
                        /* ═══ 2. FOCUS VIEW (Speaker Fullscreen with Floating Self PiP) ═══ */
                        <div style={{
                            position: 'relative',
                            flex: 1,
                            width: '100%',
                            height: '100%',
                            overflow: 'hidden'
                        }}>
                            {/* Fullscreen Remote Video */}
                            <div style={{
                                position: 'absolute',
                                inset: 0,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                backgroundColor: '#0F172A'
                            }}>
                                <video 
                                    ref={remoteFsVideoRef}
                                    autoPlay 
                                    playsInline 
                                    style={{
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'cover',
                                        display: partnerVideoOn ? 'block' : 'none'
                                    }} 
                                />
                                {!partnerVideoOn && (
                                    <div style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        gap: '14px'
                                    }}>
                                        <div style={{
                                            width: '108px',
                                            height: '108px',
                                            borderRadius: '50%',
                                            background: 'linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%)',
                                            boxShadow: '0 0 50px rgba(59, 130, 246, 0.5)',
                                            color: '#FFFFFF',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontWeight: 800,
                                            fontSize: '42px',
                                            border: '4px solid rgba(255, 255, 255, 0.2)'
                                        }}>
                                            {config.partnerName ? config.partnerName.charAt(0).toUpperCase() : 'У'}
                                        </div>
                                        <span style={{ color: '#FFFFFF', fontSize: '17px', fontWeight: 700 }}>
                                            {config.partnerName || 'Собеседник'}
                                        </span>
                                    </div>
                                )}
                            </div>

                            {/* Floating Self-View Card (Corner PiP) */}
                            <div 
                                onClick={() => setPipSwap(prev => !prev)}
                                style={{
                                    position: 'absolute',
                                    top: '64px',
                                    right: '14px',
                                    width: isMobile ? '105px' : '150px',
                                    height: isMobile ? '145px' : '200px',
                                    borderRadius: '20px',
                                    backgroundColor: '#0F172A',
                                    border: '2px solid rgba(255, 255, 255, 0.25)',
                                    boxShadow: '0 16px 40px rgba(0, 0, 0, 0.7)',
                                    overflow: 'hidden',
                                    cursor: 'pointer',
                                    zIndex: 20
                                }}>
                                <video 
                                    ref={localFsVideoRef}
                                    autoPlay 
                                    playsInline 
                                    muted 
                                    style={{
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'cover',
                                        transform: cameraFacing === 'user' ? 'scaleX(-1)' : 'none',
                                        display: isCameraOn ? 'block' : 'none'
                                    }} 
                                />
                                {!isCameraOn && (
                                    <div style={{
                                        width: '100%',
                                        height: '100%',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        background: '#7D39EB',
                                        color: '#FFFFFF',
                                        fontSize: '24px',
                                        fontWeight: 700
                                    }}>
                                        Я
                                    </div>
                                )}
                                <span style={{
                                    position: 'absolute',
                                    bottom: '6px',
                                    left: '8px',
                                    color: '#FFFFFF',
                                    fontSize: '10px',
                                    fontWeight: 700,
                                    backgroundColor: 'rgba(0,0,0,0.6)',
                                    padding: '2px 5px',
                                    borderRadius: '4px'
                                }}>
                                    Вы
                                </span>
                            </div>
                        </div>
                    )}

                    {/* ═══ Ultra-Premium Bottom Floating Glass Controls Island ═══ */}
                    <div style={{
                        position: 'absolute',
                        bottom: 'max(16px, env(safe-area-inset-bottom))',
                        left: '50%',
                        transform: 'translateX(-50%)',
                        display: 'flex',
                        alignItems: 'center',
                        gap: isMobile ? '8px' : '12px',
                        padding: isMobile ? '6px 12px' : '8px 18px',
                        borderRadius: '9999px',
                        backgroundColor: 'rgba(15, 23, 42, 0.82)',
                        backdropFilter: 'blur(32px) saturate(190%)',
                        WebkitBackdropFilter: 'blur(32px) saturate(190%)',
                        border: '1px solid rgba(255, 255, 255, 0.18)',
                        boxShadow: '0 20px 50px rgba(0, 0, 0, 0.7)',
                        zIndex: 110
                    }}>
                        {/* 1. Mic Button */}
                        <button
                            type="button"
                            onClick={toggleMic}
                            title={isMicOn ? 'Выключить микрофон' : 'Включить микрофон'}
                            style={fullscreenControlStyle(isMicOn, 'rgba(255, 255, 255, 0.22)', '#EF4444', isMobile)}>
                            {isMicOn ? <Mic size={isMobile ? 18 : 20} color="#FFFFFF" /> : <MicOff size={isMobile ? 18 : 20} color="#FFFFFF" />}
                        </button>

                        {/* 2. Camera Button */}
                        <button
                            type="button"
                            onClick={toggleCamera}
                            title={isCameraOn ? 'Выключить камеру' : 'Включить камеру'}
                            style={fullscreenControlStyle(isCameraOn, 'rgba(255, 255, 255, 0.22)', '#EF4444', isMobile)}>
                            {isCameraOn ? <Video size={isMobile ? 18 : 20} color="#FFFFFF" /> : <VideoOff size={isMobile ? 18 : 20} color="#FFFFFF" />}
                        </button>

                        {/* 3. Flip Camera Button (mobile) */}
                        {isMobile && (
                            <button
                                type="button"
                                onClick={handleFlipCamera}
                                title="Переключить камеру (передняя/задняя)"
                                style={{
                                    ...fullscreenControlStyle(true, 'rgba(255, 255, 255, 0.18)', null, isMobile),
                                    transform: isFlippingCamera ? 'rotate(180deg)' : 'none',
                                    transition: 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)'
                                }}>
                                <RefreshCw size={17} color="#FFFFFF" />
                            </button>
                        )}

                        {/* 4. Chat Drawer Button */}
                        <button
                            type="button"
                            onClick={() => setActiveTab(prev => prev === 'chat' ? null : 'chat')}
                            title="Чат урока"
                            style={fullscreenControlStyle(activeTab === 'chat', '#7D39EB', 'rgba(255, 255, 255, 0.18)', isMobile)}>
                            <MessageSquare size={isMobile ? 18 : 20} color="#FFFFFF" />
                        </button>

                        {/* 5. End Call / Return Button (Red pill) */}
                        <button
                            type="button"
                            onClick={() => setIsVideoFullscreen(false)}
                            title="Вернуться к интерактивной доске"
                            style={{
                                width: isMobile ? '46px' : '52px',
                                height: isMobile ? '46px' : '52px',
                                borderRadius: '50%',
                                border: 'none',
                                backgroundColor: '#EF4444',
                                color: '#FFFFFF',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                cursor: 'pointer',
                                boxShadow: '0 8px 24px rgba(239, 68, 68, 0.55)',
                                transition: 'transform 0.15s ease',
                                WebkitTapHighlightColor: 'transparent'
                            }}>
                            <PhoneOff size={isMobile ? 19 : 22} />
                        </button>
                    </div>
                </div>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* 8. EXIT CONFIRMATION MODAL                         */}
            {/* ═══════════════════════════════════════════════════ */}
            {exitModalOpen && (
                <div style={{
                    position: 'fixed',
                    inset: 0,
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    backdropFilter: 'blur(16px)',
                    zIndex: 200,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: '16px'
                }}>
                    <div style={{
                        width: '100%',
                        maxWidth: '400px',
                        borderRadius: '24px',
                        backgroundColor: isLight ? '#FFFFFF' : '#121214',
                        color: isLight ? '#0F172A' : '#FFFFFF',
                        border: isLight ? '1px solid rgba(0, 0, 0, 0.1)' : '1px solid rgba(255, 255, 255, 0.15)',
                        padding: '24px',
                        boxShadow: '0 25px 60px rgba(0, 0, 0, 0.5)'
                    }}>
                        <h3 style={{ margin: '0 0 8px 0', fontSize: '18px', fontWeight: 700 }}>
                            {config.userRole === 'tutor' ? 'Завершить урок?' : 'Покинуть занятие?'}
                        </h3>
                        <p style={{ margin: '0 0 20px 0', fontSize: '13px', color: isLight ? '#64748B' : '#94A3B8', lineHeight: 1.5 }}>
                            {config.userRole === 'tutor' 
                                ? 'Урок будет завершён для всех участников. Материалы и конспекты сохранятся в истории занятий.' 
                                : 'Вы сможете вернуться в комнату по ссылке, пока преподаватель проводит урок.'}
                        </p>
                        <div style={{ display: 'flex', gap: '10px' }}>
                            <button
                                type="button"
                                onClick={() => setExitModalOpen(false)}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    borderRadius: '12px',
                                    border: isLight ? '1px solid rgba(0, 0, 0, 0.1)' : '1px solid rgba(255, 255, 255, 0.15)',
                                    backgroundColor: 'transparent',
                                    color: isLight ? '#0F172A' : '#FFFFFF',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    cursor: 'pointer'
                                }}>
                                Отмена
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    if (config.userRole === 'tutor' && config.lessonId) {
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = `/classroom/${config.lessonId}/end`;
                                        const csrfInput = document.createElement('input');
                                        csrfInput.type = 'hidden';
                                        csrfInput.name = '_token';
                                        csrfInput.value = config.csrfToken;
                                        form.appendChild(csrfInput);
                                        document.body.appendChild(form);
                                        form.submit();
                                    } else {
                                        window.location.href = config.exitUrl || '/';
                                    }
                                }}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    borderRadius: '12px',
                                    border: 'none',
                                    backgroundColor: '#EF4444',
                                    color: '#FFFFFF',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    textAlign: 'center',
                                    cursor: 'pointer'
                                }}>
                                {config.userRole === 'tutor' ? 'Завершить' : 'Выйти'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
            {/* ═══════════════════════════════════════════════════ */}
            {/* 10. ONBOARDING TOUR MODAL                           */}
            {/* ═══════════════════════════════════════════════════ */}
            <ClassroomOnboardingTour
                isOpen={isTourOpen}
                onClose={() => setIsTourOpen(false)}
                isTutor={config.userRole === 'tutor'}
                userName={config.userName || ''}
            />
        </div>
    );
}

function dockButtonStyle(isLight, bg, isMobile) {
    return {
        flex: isMobile ? '1 1 0%' : '0 0 auto',
        width: isMobile ? 'auto' : '52px',
        minWidth: 0,
        maxWidth: isMobile ? '48px' : '56px',
        height: isMobile ? '46px' : '50px',
        padding: '2px',
        borderRadius: '14px',
        backgroundColor: bg,
        border: '1px solid transparent',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '2px',
        cursor: 'pointer',
        WebkitTapHighlightColor: 'transparent',
        touchAction: 'manipulation',
        transition: 'all 0.15s ease'
    };
}

function dockLabelStyle(isLight) {
    return {
        fontSize: '9px',
        fontWeight: 600,
        lineHeight: 1,
        color: isLight ? '#475569' : '#94A3B8',
        whiteSpace: 'nowrap',
        overflow: 'hidden',
        textOverflow: 'ellipsis',
        maxWidth: '44px',
        pointerEvents: 'none'
    };
}

function sheetItemStyle(isLight) {
    return {
        display: 'flex',
        alignItems: 'center',
        gap: '10px',
        padding: '12px 14px',
        borderRadius: '14px',
        border: isLight ? '1px solid rgba(0, 0, 0, 0.06)' : '1px solid rgba(255, 255, 255, 0.1)',
        backgroundColor: isLight ? '#F8FAFC' : 'rgba(255, 255, 255, 0.05)',
        color: isLight ? '#0F172A' : '#FFFFFF',
        fontSize: '13px',
        fontWeight: 600,
        cursor: 'pointer',
        textAlign: 'left'
    };
}

function fullscreenControlStyle(isOn, activeBg, inactiveBg, isMobile) {
    return {
        width: isMobile ? '46px' : '52px',
        height: isMobile ? '46px' : '52px',
        borderRadius: '50%',
        border: '1px solid rgba(255, 255, 255, 0.15)',
        backgroundColor: isOn ? (activeBg || 'rgba(255, 255, 255, 0.22)') : (inactiveBg || '#EF4444'),
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        cursor: 'pointer',
        boxShadow: isOn ? '0 4px 16px rgba(0, 0, 0, 0.3)' : '0 6px 20px rgba(239, 68, 68, 0.45)',
        transition: 'all 0.15s ease',
        WebkitTapHighlightColor: 'transparent'
    };
}
