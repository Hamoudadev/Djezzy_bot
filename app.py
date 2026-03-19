# -*- coding: utf-8 -*-
from flask import Flask, request
import requests

app = Flask(__name__)

# --- الإعدادات النهائية ---
PAGE_ACCESS_TOKEN = "EAAM49zBR2l0BQyiIuuaVUX7aHuVcH43TGuZCkGwUkZANWIttgzZBb66vzD9AwPQaYqVOWMTEEyJAmUqAnW7ZAxyuJhtxOZCZAR9ddZCZCSTkRkIpGwmhUscnWQlwj3hwyPRsQK52nxZCmQayMZAuJ1GhZB8C1rEZCwaaLSWHZChL856eIyrv7BMmBnBxw9u0C7vjI5LZBrBl7ijAZDZD"
VERIFY_TOKEN = "VexByte2026"

# دالة إرسال الأزرار (Quick Replies) كيما اللي في الصورة
def send_main_menu(recipient_id, text):
    url = f"https://graph.facebook.com/v19.0/me/messages?access_token={PAGE_ACCESS_TOKEN}"
    payload = {
        "recipient": {"id": recipient_id},
        "message": {
            "text": text,
            "quick_replies": [
                {"content_type": "text", "title": "🎉 تفعيل 2 جيغا", "payload": "ACT_2GB"},
                {"content_type": "text", "title": "💰 كم هو رصيدي", "payload": "CHECK_BAL"},
                {"content_type": "text", "title": "🌙 عروض رمضان", "payload": "RAMADAN"}
            ]
        }
    }
    requests.post(url, json=payload)

@app.route('/', methods=['GET'])
def verify():
    if request.args.get("hub.mode") == "subscribe" and request.args.get("hub.verify_token") == VERIFY_TOKEN:
        return request.args.get("hub.challenge"), 200
    return "Djezzy VIP Pro Online", 200

@app.route('/', methods=['POST'])
def webhook():
    data = request.get_json()
    if data.get("object") == "page":
        for entry in data.get("entry"):
            for event in entry.get("messaging"):
                sender_id = event["sender"]["id"]
                
                if event.get("message"):
                    msg_text = event["message"].get("text", "").strip()

                    # الاستجابة للرسائل
                    if msg_text.lower() in ["start", "سلام", "قائمة"]:
                        send_main_menu(sender_id, "مرحباً بك في جيزي VIP! 🚀\nاختر من الأزرار أسفله:")
                    
                    elif "تفعيل 2 جيغا" in msg_text:
                        send_main_menu(sender_id, "✅ اخترت عرض 2GB.\nأرسل رقم هاتفك الآن (07XXXXXXXX):")
                    
                    elif "رصيدي" in msg_text:
                        send_main_menu(sender_id, "🔍 جاري التحقق... أرسل رقم هاتفك:")
                    
                    else:
                        send_main_menu(sender_id, "استخدم الأزرار أسفله للتحكم في البوت:")

    return "ok", 200

if __name__ == '__main__':
    app.run()
