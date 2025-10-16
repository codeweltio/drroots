import express from "express";
import nodemailer from "nodemailer";
import dotenv from "dotenv";

dotenv.config();

const app = express();


// trust cPanel reverse proxy
app.set("trust proxy", true);

// parse JSON & form data
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// serve static files (contact form)
app.use(express.static("public"));

// health check (Passenger uses this sometimes)
app.get("/healthz", (req, res) => res.send("ok"));

// POST /api/contact  (called by the form)
app.post("/api/contact", async (req, res) => {
  try {
    const { name, email, message, phone } = req.body;

    if (!name || !email || !message) {
      return res.status(400).json({ ok: false, error: "Missing fields" });
    }

    // Create SMTP transporter (use your cPanel mailbox)
    const transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST,      // e.g. mail.drrootsdc.in
      port: Number(process.env.SMTP_PORT || 465),
      secure: process.env.SMTP_SECURE === "true", // true for 465, false for 587
      auth: {
        user: process.env.SMTP_USER,    // e.g. noreply@drrootsdc.in
        pass: process.env.SMTP_PASS     // mailbox password
      }
    });

    // Email to site owner
    const ownerMail = await transporter.sendMail({
      from: `"${process.env.FROM_NAME}" <${process.env.FROM_EMAIL}>`,
      to: process.env.TO_EMAIL, // where you receive messages
      replyTo: email,           // so you can reply to the visitor
      subject: `New contact from ${name}`,
      text: `Name: ${name}
Email: ${email}
Phone: ${phone || "-"}
IP: ${req.ip}

Message:
${message}`,
    });

    // Optional: auto-reply to the sender
    if (process.env.SEND_AUTOREPLY === "true") {
      await transporter.sendMail({
        from: `"${process.env.FROM_NAME}" <${process.env.FROM_EMAIL}>`,
        to: email,
        subject: "We received your message",
        text:
`Hi ${name},

Thanks for contacting DR ROOTS DC. We’ve received your message and will get back to you soon.

Regards,
DR ROOTS DC`
      });
    }

    res.json({ ok: true, id: ownerMail.messageId });
  } catch (err) {
    console.error(err);
    res.status(500).json({ ok: false, error: "Failed to send email" });
  }
});

// Use cPanel provided PORT if present, else 3000
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
