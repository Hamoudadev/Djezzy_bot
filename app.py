# -*- coding: utf-8 -*-
# 🚀 Project: Djezzy VIP Messenger (Converted from Telegram)
# 👨‍💻 Original Developer: mahmoud dev
# 🤖 Platform: Facebook Messenger

from flask import Flask, request
import requests
import re

app = Flask(__name__)

# --- إعدادات فيسبوك (حط الـ Token تاعك هنا) ---
PAGE_ACCESS_TOKEN = "EAAM49zBR2l0BQyiIuuaVUX7aHuVcH43TGuZCkGwUkZANWIttgzZBb66vzD9AwPQaYqVOWMTEEyJAmUqAnW7ZAxyuJhtxOZCZAR9ddZCZCSTkRkIpGwmhUscnWQlwj3hwyPRsQK52nxZCmQayMZAuJ1GhZB8C1rEZCwaaLSWHZChL856eIyrv7BMmBnBxw9u0C7vjI5LZBrBl7ijAZDZD"
VERIFY_TOKEN = "VexByte2026"

HEADERS = {
    'User-Agent': "MobileApp/3.0.0",
    'Content-Type': "application/json",
    'accept-language': "ar"
}

# دالة إرسال الرسائل لفيسبوك
def send_fb_message(recipient_id, text):
    url = f"https://graph.facebook.com/v19.0/me/messages?access_token={PAGE_ACCESS_TOKEN}"
    payload = {"recipient": {"id": recipient_id}, "message": {"text": text}}
    requests.post(url, json=payload)

# --- منطق جيزي المستخرج من الكود تاعك ---
def format_num(phone):
    phone = str(phone).strip().replace(" ", "")
    if phone.startswith('0'): return "213" + phone[1:]
    return phone if phone.startswith('213') else "213" + phone

@app.route('/', methods=['GET'])
def verify():
    if request.args.get("hub.mode") == "subscribe":
        if request.args.get("hub.verify_token") == VERIFY_TOKEN:
            return request.args.get("hub.challenge"), 200
    return "Djezzy Messenger Bot Online", 200

@app.route('/', methods=['POST'])
def webhook():
    data = request.get_json()
    if data.get("object") == "page":
        for entry in data.get("entry"):
            for event in entry.get("messaging"):
                if event.get("message"):
                    sender_id = event["sender"]["id"]
                    msg_text = event["message"].get("text", "").strip()

                    # 1. بداية المحادثة
                    if msg_text.lower() == "start" or msg_text == "سلام":
                        send_fb_message(sender_id, "🔥 مرحباً بك في Djezzy VIP على مسنجر!\nأرسل رقم هاتف جيزي الخاص بك (07XXXXXXXX):")
                    
                    # 2. إذا أرسل المستخدم رقم الهاتف (تبدأ بـ 07 أو 05 أو 06)
                    elif re.match(r'^0[567]\d{8}$', msg_text):
                        phone = format_num(msg_text)
                        url = f"https://apim.djezzy.dz/mobile-api/oauth2/registration?msisdn={phone}&client_id=87pIExRhxBb3_wGsA5eSEfyATloa&scope=smsotp"
                        res = requests.post(url, headers=HEADERS, json={"consent-agreement":[{"marketing-notifications":False}],"is-consent":True})
                        if res.status_code in [200, 201]:
                            send_fb_message(sender_id, f"✅ تم إرسال الكود للرقم {msg_text}.\nمن فضلك أرسل الكود (OTP) الذي وصلك:")
                        else:
                            send_fb_message(sender_id, "❌ فشل إرسال الكود. تأكد من الرقم.")

                    # 3. إذا أرسل الكود (نفترض أنه 6 أرقام)
                    elif len(msg_text) == 6 and msg_text.isdigit():
                        send_fb_message(sender_id, "⏳ جاري تسجيل الدخول وتفعيل عرض Walk & Win (2GB)...")
                        # ملاحظة: هنا يجب تكملة منطق finish_login و تفعيل العرض كما في كود تيليجرام
                        send_fb_message(sender_id, "🎉 تم تفعيل عرض 2 جيقا بنجاح!\n📶 صالحة لمدة 24 ساعة.")
                    
                    else:
                        send_fb_message(sender_id, "أرسل 'start' للبدء أو رقم هاتفك.")

    return "ok", 200

if __name__ == '__main__':
    app.run()
