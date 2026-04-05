#!/usr/bin/env python3
"""Generate WeChat tabBar PNGs (81x81) — 3 tabs: 首页 / 灵感 / 我的。主题色 #7B57E4。"""
from __future__ import annotations

import struct
import zlib
from pathlib import Path

W = H = 81
INACTIVE = (142, 149, 163, 255)
ACTIVE = (123, 87, 228, 255)
TRANSPARENT = (0, 0, 0, 0)


def buf_clear(buf: bytearray, rgba: tuple[int, int, int, int]) -> None:
    r, g, b, a = rgba
    for i in range(0, len(buf), 4):
        buf[i : i + 4] = bytes([r, g, b, a])


def px(buf: bytearray, x: int, y: int, rgba: tuple[int, int, int, int]) -> None:
    if 0 <= x < W and 0 <= y < H:
        i = (y * W + x) * 4
        buf[i : i + 4] = bytes(rgba)


def fill_rect(buf: bytearray, x0: int, y0: int, x1: int, y1: int, rgba: tuple) -> None:
    for y in range(max(0, y0), min(H, y1)):
        for x in range(max(0, x0), min(W, x1)):
            px(buf, x, y, rgba)


def thick_seg(buf: bytearray, x0: int, y0: int, x1: int, y1: int, rgba: tuple, th: int = 2) -> None:
    dx = abs(x1 - x0)
    dy = -abs(y1 - y0)
    sx = 1 if x0 < x1 else -1
    sy = 1 if y0 < y1 else -1
    err = dx + dy
    x, y = x0, y0
    while True:
        for ox in range(-th, th + 1):
            for oy in range(-th, th + 1):
                px(buf, x + ox, y + oy, rgba)
        if x == x1 and y == y1:
            break
        e2 = 2 * err
        if e2 >= dy:
            err += dy
            x += sx
        if e2 <= dx:
            err += dx
            y += sy


def icon_tab_home(buf: bytearray, color: tuple) -> None:
    """小房子轮廓（首页 Tab）"""
    buf_clear(buf, TRANSPARENT)
    # 尖顶
    for y in range(14, 34):
        half = 3 + int((y - 14) * 1.05)
        for x in range(40 - half, 40 + half + 1):
            px(buf, x, y, color)
    # 房身
    fill_rect(buf, 26, 32, 54, 64, color)
    # 门洞（透出透明）
    for y in range(44, 64):
        for x in range(35, 45):
            px(buf, x, y, TRANSPARENT)


def icon_tab_inspire(buf: bytearray, color: tuple) -> None:
    """灯泡轮廓（灵感）"""
    buf_clear(buf, TRANSPARENT)
    cx, cy, r = 40, 30, 12
    for y in range(H):
        for x in range(W):
            d = ((x - cx) ** 2 + (y - cy) ** 2) ** 0.5
            if 9 <= d <= r + 1:
                px(buf, x, y, color)
    fill_rect(buf, 34, 38, 46, 48, color)
    fill_rect(buf, 32, 48, 48, 54, color)
    fill_rect(buf, 36, 54, 44, 58, color)


def icon_tab_me(buf: bytearray, color: tuple) -> None:
    """用户头像轮廓"""
    buf_clear(buf, TRANSPARENT)
    cx, cy, r = 40, 30, 9
    for y in range(H):
        for x in range(W):
            d = ((x - cx) ** 2 + (y - cy) ** 2) ** 0.5
            if 7 <= d <= r + 1:
                px(buf, x, y, color)
    for y in range(48, 66):
        half = int(10 + (y - 48) * 0.55)
        for x in range(40 - half, 40 + half):
            px(buf, x, y, color)


def write_png(path: Path, buf: bytearray) -> None:
    raw = bytearray()
    for y in range(H):
        raw.append(0)
        raw.extend(buf[y * W * 4 : (y + 1) * W * 4])
    compressed = zlib.compress(bytes(raw), 9)

    def chunk(tag: bytes, data: bytes) -> bytes:
        crc = zlib.crc32(tag + data) & 0xFFFFFFFF
        return struct.pack(">I", len(data)) + tag + data + struct.pack(">I", crc)

    ihdr = struct.pack(">IIBBBBB", W, H, 8, 6, 0, 0, 0)
    data = b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", ihdr) + chunk(b"IDAT", compressed) + chunk(b"IEND", b"")
    path.write_bytes(data)


def main() -> None:
    # uni-app Vite 只打包 src/static，根目录 static/ 不会进 mp-weixin 产物
    out = Path(__file__).resolve().parent.parent / "src" / "static" / "tabbar"
    out.mkdir(parents=True, exist_ok=True)
    icons = [
        ("tab-home", icon_tab_home),
        ("tab-inspire", icon_tab_inspire),
        ("tab-me", icon_tab_me),
    ]
    for name, draw in icons:
        for suffix, col in (("", INACTIVE), ("-active", ACTIVE)):
            buf = bytearray(W * H * 4)
            draw(buf, col)
            p = out / f"{name}{suffix}.png"
            write_png(p, buf)
            print("wrote", p)


if __name__ == "__main__":
    main()
