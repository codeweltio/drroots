// server/index.ts
import express2 from "express";
import path3 from "path";

// server/routes.ts
import { createServer } from "http";

// server/storage.ts
import { randomUUID } from "crypto";
var MemStorage = class {
  doctors;
  timings;
  slotConfig;
  appointments;
  contactMessages;
  rateLimitMap;
  constructor() {
    this.appointments = /* @__PURE__ */ new Map();
    this.contactMessages = /* @__PURE__ */ new Map();
    this.rateLimitMap = /* @__PURE__ */ new Map();
    this.doctors = [
      {
        id: "1",
        name: "Dr. Arun B T",
        title: "BDS, MDS, FPFA, FWFO",
        specialization: "Orthodontist and Dentofacial Orthopedician",
        credentials: "BDS, MDS, FPFA, FWFO \u2014 Invisalign-certified Orthodontist and Dentofacial Orthopedician; Fellow of the Pierre Fauchard Academy (FPFA) and the World Federation of Orthodontists (FWFO). Director, Dr. Roots Denta Care.",
        bio: "Dr. Arun B T is an accomplished orthodontist and dentofacial orthopedician, dedicated to delivering high-quality, patient-centered dental care. He completed his BDS from KMCT Dental College, Calicut, and MDS from A. B. Shetty Memorial Institute of Dental Sciences, Mangalore \u2014 one of India\u2019s most prestigious dental institutions.\n\nHis areas of expertise include TADs, aligners, self-ligating braces, and MARPE, with over 500 successful orthodontic cases to his credit. He is a Fellow of the Pierre Fauchard Academy and the World Federation of Orthodontists, and the only Invisalign-certified orthodontist in Palakkad.\n\nIn addition to leading Dr. Roots Denta Care, Dr. Arun serves as a consulting specialist at several reputed clinics and hospitals across Palakkad and Malappuram. Under his guidance, Dr. Roots Denta Care has grown into a trusted multi-specialty dental center known for precision, technology, and compassionate care.",
        image: "/assets/generated_images/Dr._Arun_Nair_portrait_2e54569d.png",
        experience: "8+ years"
      },
      {
        id: "2",
        name: "Dr. Sreejith C.K.",
        title: "BDS, MDS",
        specialization: "General & Restorative Dentistry",
        credentials: "BDS, MDS",
        bio: "Experienced dental surgeon with focus on preventive care, restorative treatments and patient comfort using modern techniques and materials.",
        image: "/assets/generated_images/Dr._Rajesh_Kumar_portrait_4bef460c.png",
        experience: "8+ years"
      },
      {
        id: "3",
        name: "Dr. Sreelakshmi C.",
        title: "BDS, MDS",
        specialization: "Periodontist",
        credentials: "BDS, MDS (Periodontist)",
        bio: "Dr. Sreelekshmi is a highly skilled periodontist and laser dentistry expert dedicated to helping patients achieve confident, healthy smiles. Her passion lies in promoting optimal gum health and enhancing dental esthetics through personalized, patient-centered care. With advanced expertise in laser procedures and a gentle, compassionate approach, she ensures every patient receives exceptional results and a truly comfortable dental\xA0experience..",
        image: "/assets/generated_images/Dr._Priya_Menon_portrait_783e4a1a.png",
        experience: "8+ years"
      }
    ];
    this.timings = [
      { day: "Monday", hours: "9:30 AM - 2:00 PM, 3:00 PM - 7:00 PM", isOpen: true },
      { day: "Tuesday", hours: "9:30 AM - 2:00 PM, 3:00 PM - 7:00 PM", isOpen: true },
      { day: "Wednesday", hours: "9:30 AM - 2:00 PM, 3:00 PM - 7:00 PM", isOpen: true },
      { day: "Thursday", hours: "9:30 AM - 2:00 PM, 3:00 PM - 7:00 PM", isOpen: true },
      { day: "Friday", hours: "9:30 AM - 2:00 PM, 3:00 PM - 7:00 PM", isOpen: true },
      { day: "Saturday", hours: "9:30 AM - 2:00 PM, 3:00 PM - 7:00 PM", isOpen: true },
      { day: "Sunday", hours: "Closed", isOpen: false }
    ];
    this.slotConfig = [
      { dayOfWeek: 0, slots: [], isOpen: false },
      // Sunday - Closed
      {
        dayOfWeek: 1,
        // Monday
        slots: [
          "09:30",
          "10:00",
          "10:30",
          "11:00",
          "11:30",
          "12:00",
          "12:30",
          "13:00",
          "13:30",
          "14:00",
          "15:00",
          "15:30",
          "16:00",
          "16:30",
          "17:00",
          "17:30",
          "18:00",
          "18:30"
        ],
        isOpen: true
      },
      {
        dayOfWeek: 2,
        // Tuesday
        slots: [
          "09:30",
          "10:00",
          "10:30",
          "11:00",
          "11:30",
          "12:00",
          "12:30",
          "13:00",
          "13:30",
          "14:00",
          "15:00",
          "15:30",
          "16:00",
          "16:30",
          "17:00",
          "17:30",
          "18:00",
          "18:30"
        ],
        isOpen: true
      },
      {
        dayOfWeek: 3,
        // Wednesday
        slots: [
          "09:30",
          "10:00",
          "10:30",
          "11:00",
          "11:30",
          "12:00",
          "12:30",
          "13:00",
          "13:30",
          "14:00",
          "15:00",
          "15:30",
          "16:00",
          "16:30",
          "17:00",
          "17:30",
          "18:00",
          "18:30"
        ],
        isOpen: true
      },
      {
        dayOfWeek: 4,
        // Thursday
        slots: [
          "09:30",
          "10:00",
          "10:30",
          "11:00",
          "11:30",
          "12:00",
          "12:30",
          "13:00",
          "13:30",
          "14:00",
          "15:00",
          "15:30",
          "16:00",
          "16:30",
          "17:00",
          "17:30",
          "18:00",
          "18:30"
        ],
        isOpen: true
      },
      {
        dayOfWeek: 5,
        // Friday
        slots: [
          "09:30",
          "10:00",
          "10:30",
          "11:00",
          "11:30",
          "12:00",
          "12:30",
          "13:00",
          "13:30",
          "14:00",
          "15:00",
          "15:30",
          "16:00",
          "16:30",
          "17:00",
          "17:30",
          "18:00",
          "18:30"
        ],
        isOpen: true
      },
      {
        dayOfWeek: 6,
        // Saturday
        slots: [
          "09:30",
          "10:00",
          "10:30",
          "11:00",
          "11:30",
          "12:00",
          "12:30",
          "13:00",
          "13:30",
          "14:00",
          "15:00",
          "15:30",
          "16:00",
          "16:30",
          "17:00",
          "17:30",
          "18:00",
          "18:30"
        ],
        isOpen: true
      }
    ];
  }
  async getDoctors() {
    const desiredOrder = ["1", "3", "2"];
    const indexOf = (id) => {
      const i = desiredOrder.indexOf(id);
      return i === -1 ? Number.MAX_SAFE_INTEGER : i;
    };
    return [...this.doctors].sort((a, b) => indexOf(a.id) - indexOf(b.id));
  }
  async getTimings() {
    return this.timings;
  }
  async getSlotConfig() {
    return this.slotConfig;
  }
  async createAppointment(insertAppointment) {
    const id = randomUUID();
    const appointment = {
      ...insertAppointment,
      id,
      createdAt: (/* @__PURE__ */ new Date()).toISOString(),
      status: "pending"
    };
    this.appointments.set(id, appointment);
    return appointment;
  }
  async getAppointmentsByDateAndSlot(date, slot) {
    return Array.from(this.appointments.values()).filter(
      (apt) => apt.date === date && apt.slot === slot && apt.status !== "cancelled"
    );
  }
  async getAllAppointments() {
    return Array.from(this.appointments.values());
  }
  async createContactMessage(insertMessage) {
    const id = randomUUID();
    const message = {
      ...insertMessage,
      id,
      createdAt: (/* @__PURE__ */ new Date()).toISOString()
    };
    this.contactMessages.set(id, message);
    return message;
  }
  async checkRateLimit(ip) {
    const now = Date.now();
    const lastRequest = this.rateLimitMap.get(ip);
    if (!lastRequest) {
      return true;
    }
    return now - lastRequest > 6e4;
  }
  async recordRequest(ip) {
    this.rateLimitMap.set(ip, Date.now());
  }
};
var storage = new MemStorage();

// server/services/mailer.ts
import { createTransport } from "nodemailer";
var MailerService = class {
  transporter = null;
  constructor() {
    this.initializeTransporter();
  }
  initializeTransporter() {
    const host = process.env.MAIL_HOST;
    const port = parseInt(process.env.MAIL_PORT || "587");
    const user = process.env.MAIL_USER;
    const pass = process.env.MAIL_PASS;
    if (!host || !user || !pass) {
      console.warn("SMTP credentials not fully configured. Emails will be logged to console.");
      return;
    }
    this.transporter = createTransport({
      host,
      port,
      secure: port === 465,
      auth: {
        user,
        pass
      }
    });
  }
  async sendAppointmentConfirmation(appointment, icsAttachment) {
    const from = process.env.MAIL_FROM || "noreply@drrootsdc.in";
    const clinicEmail = "info@drrootsdc.in";
    const subject = `New Appointment Request \u2014 ${appointment.date} ${appointment.slot} \u2014 ${appointment.name}`;
    const clinicBody = `
      <h2>New Appointment Request</h2>
      <p>You have received a new appointment request:</p>
      <ul>
        <li><strong>Name:</strong> ${appointment.name}</li>
        <li><strong>Phone:</strong> ${appointment.phone}</li>
        <li><strong>Email:</strong> ${appointment.email}</li>
        <li><strong>Date:</strong> ${appointment.date}</li>
        <li><strong>Time:</strong> ${appointment.slot}</li>
        <li><strong>Reason:</strong> ${appointment.reason || "Not specified"}</li>
      </ul>
      <p>Please review and confirm this appointment with the patient.</p>
    `;
    const patientBody = `
      <h2>Appointment Request Confirmation</h2>
      <p>Dear ${appointment.name},</p>
      <p>Thank you for booking an appointment with Dr. Roots Dental Clinic!</p>
      <h3>Appointment Details:</h3>
      <ul>
        <li><strong>Date:</strong> ${appointment.date}</li>
        <li><strong>Time:</strong> ${appointment.slot}</li>
        <li><strong>Reason:</strong> ${appointment.reason || "General consultation"}</li>
      </ul>
      <p>We will confirm your appointment shortly. If you need to make any changes, please contact us at +91 98765 43210 or info@drrootsdc.in.</p>
      <p>A calendar invite is attached to this email for your convenience.</p>
      <br>
      <p>Best regards,<br>Dr. Roots Dental Clinic Team</p>
    `;
    const attachments = [{
      filename: "appointment.ics",
      content: icsAttachment,
      contentType: "text/calendar"
    }];
    await this.sendEmail(from, clinicEmail, subject, clinicBody, attachments);
    await this.sendEmail(from, appointment.email, subject, patientBody, attachments);
  }
  async sendContactMessage(message) {
    const from = process.env.MAIL_FROM || "noreply@drrootsdc.in";
    const clinicEmail = "info@drrootsdc.in";
    const subject = `Contact Form: ${message.subject}`;
    const body = `
      <h2>New Contact Form Submission</h2>
      <ul>
        <li><strong>Name:</strong> ${message.name}</li>
        <li><strong>Email:</strong> ${message.email}</li>
        <li><strong>Phone:</strong> ${message.phone || "Not provided"}</li>
        <li><strong>Subject:</strong> ${message.subject}</li>
      </ul>
      <h3>Message:</h3>
      <p>${message.message.replace(/\n/g, "<br>")}</p>
    `;
    await this.sendEmail(from, clinicEmail, subject, body);
  }
  async sendEmail(from, to, subject, html, attachments) {
    const mailOptions = {
      from,
      to,
      subject,
      html,
      attachments
    };
    if (!this.transporter) {
      console.log("=".repeat(80));
      console.log("EMAIL (would be sent):");
      console.log(`From: ${from}`);
      console.log(`To: ${to}`);
      console.log(`Subject: ${subject}`);
      console.log("Body:", html);
      if (attachments) {
        console.log("Attachments:", attachments.map((a) => a.filename).join(", "));
      }
      console.log("=".repeat(80));
      return;
    }
    try {
      const info = await this.transporter.sendMail(mailOptions);
      console.log("Email sent:", info.messageId);
    } catch (error) {
      console.error("Failed to send email:", error);
      throw error;
    }
  }
};
var mailerService = new MailerService();

// server/services/icsBuilder.ts
import { createEvent } from "ics";
var IcsBuilder = class {
  generateIcs(appointment) {
    const [year, month, day] = appointment.date.split("-").map(Number);
    const [hours, minutes] = appointment.slot.split(":").map(Number);
    const start = [year, month, day, hours, minutes];
    const endMinutes = minutes + 30;
    const endHours = hours + Math.floor(endMinutes / 60);
    const adjustedEndMinutes = endMinutes % 60;
    const end = [year, month, day, endHours, adjustedEndMinutes];
    const event = {
      start,
      end,
      title: "Dental Appointment - Dr. Roots Dental Clinic",
      description: `Appointment with Dr. Roots Dental Clinic
Reason: ${appointment.reason || "General consultation"}
Patient: ${appointment.name}`,
      location: "Dr. Roots Dental Clinic, 123 Main Street, Palakkad, Kerala 678001",
      status: "CONFIRMED",
      busyStatus: "BUSY",
      organizer: { name: "Dr. Roots Dental Clinic", email: "info@drrootsdc.in" },
      attendees: [
        {
          name: appointment.name,
          email: appointment.email,
          rsvp: true,
          partstat: "NEEDS-ACTION",
          role: "REQ-PARTICIPANT"
        }
      ]
    };
    const { error, value } = createEvent(event);
    if (error) {
      console.error("Failed to create ICS:", error);
      throw new Error("Failed to generate calendar invite");
    }
    return value || "";
  }
};
var icsBuilder = new IcsBuilder();

// shared/schema.ts
import { z } from "zod";
var doctorSchema = z.object({
  id: z.string(),
  name: z.string(),
  title: z.string(),
  specialization: z.string(),
  credentials: z.string(),
  bio: z.string(),
  image: z.string(),
  experience: z.string()
});
var timingSchema = z.object({
  day: z.string(),
  hours: z.string(),
  isOpen: z.boolean()
});
var slotConfigSchema = z.object({
  dayOfWeek: z.number().min(0).max(6),
  slots: z.array(z.string()),
  isOpen: z.boolean()
});
var appointmentSchema = z.object({
  id: z.string(),
  name: z.string().min(2, "Name must be at least 2 characters"),
  phone: z.string().regex(/^[0-9]{10}$/, "Please enter a valid 10-digit phone number"),
  email: z.string().email("Please enter a valid email address"),
  date: z.string(),
  slot: z.string(),
  reason: z.string().optional(),
  consent: z.boolean().refine((val) => val === true, {
    message: "You must agree to the terms and conditions"
  }),
  website: z.string().optional(),
  // honeypot field
  createdAt: z.string(),
  status: z.enum(["pending", "confirmed", "cancelled"]).default("pending")
});
var insertAppointmentSchema = appointmentSchema.omit({
  id: true,
  createdAt: true,
  status: true
});
var contactMessageSchema = z.object({
  id: z.string(),
  name: z.string().min(2, "Name must be at least 2 characters"),
  email: z.string().email("Please enter a valid email address"),
  phone: z.string().optional(),
  subject: z.string().min(5, "Subject must be at least 5 characters"),
  message: z.string().min(10, "Message must be at least 10 characters"),
  website: z.string().optional(),
  // honeypot field
  createdAt: z.string()
});
var insertContactMessageSchema = contactMessageSchema.omit({
  id: true,
  createdAt: true
});
var slotAvailabilitySchema = z.object({
  date: z.string(),
  slots: z.array(z.object({
    time: z.string(),
    available: z.boolean()
  }))
});

// server/routes.ts
async function registerRoutes(app2) {
  app2.get("/api/doctors", async (req, res) => {
    try {
      const doctors = await storage.getDoctors();
      res.json(doctors);
    } catch (error) {
      console.error("Failed to get doctors:", error);
      res.status(500).json({ error: "Failed to fetch doctors" });
    }
  });
  app2.get("/api/timings", async (req, res) => {
    try {
      const timings = await storage.getTimings();
      res.json(timings);
    } catch (error) {
      console.error("Failed to get timings:", error);
      res.status(500).json({ error: "Failed to fetch timings" });
    }
  });
  app2.get("/api/slots", async (req, res) => {
    try {
      const { date } = req.query;
      if (!date || typeof date !== "string") {
        return res.status(400).json({ error: "Date parameter is required" });
      }
      const dateObj = /* @__PURE__ */ new Date(date + "T00:00:00");
      const dayOfWeek = dateObj.getDay();
      const slotConfigs = await storage.getSlotConfig();
      const dayConfig = slotConfigs.find((config) => config.dayOfWeek === dayOfWeek);
      if (!dayConfig || !dayConfig.isOpen) {
        return res.json({ date, slots: [] });
      }
      const slots = await Promise.all(
        dayConfig.slots.map(async (time) => {
          const existingAppointments = await storage.getAppointmentsByDateAndSlot(date, time);
          return {
            time,
            available: existingAppointments.length === 0
          };
        })
      );
      res.json({ date, slots });
    } catch (error) {
      console.error("Failed to get slots:", error);
      res.status(500).json({ error: "Failed to fetch available slots" });
    }
  });
  app2.post("/api/appointments", async (req, res) => {
    try {
      if (req.body.website) {
        return res.status(400).json({ error: "Invalid submission" });
      }
      const clientIp = req.ip || req.socket.remoteAddress || "unknown";
      const canProceed = await storage.checkRateLimit(clientIp);
      if (!canProceed) {
        return res.status(429).json({ error: "Too many requests. Please wait a minute before trying again." });
      }
      const validationResult = insertAppointmentSchema.safeParse(req.body);
      if (!validationResult.success) {
        return res.status(400).json({ error: validationResult.error.errors[0].message });
      }
      const appointmentData = validationResult.data;
      const appointmentDate = /* @__PURE__ */ new Date(appointmentData.date + "T00:00:00");
      const today = /* @__PURE__ */ new Date();
      today.setHours(0, 0, 0, 0);
      if (appointmentDate < today) {
        return res.status(400).json({ error: "Cannot book appointments in the past" });
      }
      const maxDate = /* @__PURE__ */ new Date();
      maxDate.setDate(maxDate.getDate() + 60);
      if (appointmentDate > maxDate) {
        return res.status(400).json({ error: "Cannot book appointments more than 60 days in advance" });
      }
      const existingAppointments = await storage.getAppointmentsByDateAndSlot(
        appointmentData.date,
        appointmentData.slot
      );
      if (existingAppointments.length > 0) {
        return res.status(409).json({ error: "This time slot is already booked. Please select another time." });
      }
      const appointment = await storage.createAppointment(appointmentData);
      const ics = icsBuilder.generateIcs(appointment);
      await mailerService.sendAppointmentConfirmation(appointment, ics);
      await storage.recordRequest(clientIp);
      res.status(201).json(appointment);
    } catch (error) {
      console.error("Failed to create appointment:", error);
      res.status(500).json({ error: "Failed to book appointment. Please try again." });
    }
  });
  app2.post("/api/contact", async (req, res) => {
    try {
      if (req.body.website) {
        return res.status(400).json({ error: "Invalid submission" });
      }
      const clientIp = req.ip || req.socket.remoteAddress || "unknown";
      const canProceed = await storage.checkRateLimit(clientIp);
      if (!canProceed) {
        return res.status(429).json({ error: "Too many requests. Please wait a minute before trying again." });
      }
      const validationResult = insertContactMessageSchema.safeParse(req.body);
      if (!validationResult.success) {
        return res.status(400).json({ error: validationResult.error.errors[0].message });
      }
      const messageData = validationResult.data;
      const message = await storage.createContactMessage(messageData);
      await mailerService.sendContactMessage(message);
      await storage.recordRequest(clientIp);
      res.status(201).json(message);
    } catch (error) {
      console.error("Failed to send contact message:", error);
      res.status(500).json({ error: "Failed to send message. Please try again." });
    }
  });
  app2.get("/sitemap.xml", (req, res) => {
    const baseUrl = `${req.protocol}://${req.get("host")}`;
    const pages = ["/", "/services", "/doctors", "/gallery", "/contact"];
    const sitemap = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${pages.map((page) => `  <url>
    <loc>${baseUrl}${page}</loc>
    <lastmod>${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>${page === "/" ? "1.0" : "0.8"}</priority>
  </url>`).join("\n")}
</urlset>`;
    res.header("Content-Type", "application/xml");
    res.send(sitemap);
  });
  app2.get("/robots.txt", (req, res) => {
    const baseUrl = `${req.protocol}://${req.get("host")}`;
    const robots = `User-agent: *
Allow: /

Sitemap: ${baseUrl}/sitemap.xml`;
    res.header("Content-Type", "text/plain");
    res.send(robots);
  });
  const httpServer = createServer(app2);
  return httpServer;
}

// server/vite.ts
import express from "express";
import fs from "fs";
import path2 from "path";
import { createServer as createViteServer, createLogger } from "vite";

// vite.config.ts
import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";
import { fileURLToPath } from "url";
import runtimeErrorOverlay from "@replit/vite-plugin-runtime-error-modal";
var __filename = fileURLToPath(import.meta.url);
var __dirname = path.dirname(__filename);
var vite_config_default = defineConfig(async () => {
  const plugins = [react(), runtimeErrorOverlay()];
  if (process.env.NODE_ENV !== "production" && process.env.REPL_ID) {
    const carto = await import("@replit/vite-plugin-cartographer").then((m) => m.cartographer());
    const devBanner = await import("@replit/vite-plugin-dev-banner").then((m) => m.devBanner());
    plugins.push(carto, devBanner);
  }
  return {
    plugins,
    base: "./",
    // ✅ make asset paths relative so plain hosting works
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "client", "src"),
        "@shared": path.resolve(__dirname, "shared"),
        "@assets": path.resolve(__dirname, "attached_assets")
      }
    },
    root: path.resolve(__dirname, "client"),
    build: {
      outDir: path.resolve(__dirname, "dist"),
      // ✅ single folder to upload
      emptyOutDir: true
    },
    server: {
      fs: { strict: true, deny: ["**/.*"] }
    }
  };
});

// server/vite.ts
import { nanoid } from "nanoid";
var viteLogger = createLogger();
function log(message, source = "express") {
  const formattedTime = (/* @__PURE__ */ new Date()).toLocaleTimeString("en-US", {
    hour: "numeric",
    minute: "2-digit",
    second: "2-digit",
    hour12: true
  });
  console.log(`${formattedTime} [${source}] ${message}`);
}
async function setupVite(app2, server) {
  const serverOptions = {
    middlewareMode: true,
    hmr: { server },
    allowedHosts: true
  };
  const userConfig = typeof vite_config_default === "function" ? await vite_config_default() : vite_config_default;
  const vite = await createViteServer({
    ...userConfig,
    configFile: false,
    customLogger: {
      ...viteLogger,
      error: (msg, options) => {
        viteLogger.error(msg, options);
        process.exit(1);
      }
    },
    server: serverOptions,
    appType: "custom"
  });
  app2.use(vite.middlewares);
  app2.use("*", async (req, res, next) => {
    const url = req.originalUrl;
    try {
      const clientTemplate = path2.resolve(
        import.meta.dirname,
        "..",
        "client",
        "index.html"
      );
      let template = await fs.promises.readFile(clientTemplate, "utf-8");
      template = template.replace(
        `src="/src/main.tsx"`,
        `src="/src/main.tsx?v=${nanoid()}"`
      );
      const page = await vite.transformIndexHtml(url, template);
      res.status(200).set({ "Content-Type": "text/html" }).end(page);
    } catch (e) {
      vite.ssrFixStacktrace(e);
      next(e);
    }
  });
}
function serveStatic(app2) {
  const distPath = path2.resolve(import.meta.dirname);
  if (!fs.existsSync(distPath)) {
    throw new Error(
      `Could not find the build directory: ${distPath}, make sure to run 'npm run build' first`
    );
  }
  app2.use(express.static(distPath));
  app2.use("*", (_req, res) => {
    res.sendFile(path2.resolve(distPath, "index.html"));
  });
}

// server/index.ts
var app = express2();
app.use(express2.json());
app.use(express2.urlencoded({ extended: false }));
app.use(
  "/assets",
  express2.static(path3.resolve(import.meta.dirname, "..", "attached_assets"))
);
app.use((req, res, next) => {
  const start = Date.now();
  const path4 = req.path;
  let capturedJsonResponse = void 0;
  const originalResJson = res.json;
  res.json = function(bodyJson, ...args) {
    capturedJsonResponse = bodyJson;
    return originalResJson.apply(res, [bodyJson, ...args]);
  };
  res.on("finish", () => {
    const duration = Date.now() - start;
    if (path4.startsWith("/api")) {
      let logLine = `${req.method} ${path4} ${res.statusCode} in ${duration}ms`;
      if (capturedJsonResponse) {
        logLine += ` :: ${JSON.stringify(capturedJsonResponse)}`;
      }
      if (logLine.length > 80) {
        logLine = logLine.slice(0, 79) + "\u2026";
      }
      log(logLine);
    }
  });
  next();
});
(async () => {
  const server = await registerRoutes(app);
  app.use((err, _req, res, _next) => {
    const status = err.status || err.statusCode || 500;
    const message = err.message || "Internal Server Error";
    res.status(status).json({ message });
    throw err;
  });
  if (app.get("env") === "development") {
    await setupVite(app, server);
  } else {
    serveStatic(app);
  }
  const port = Number(process.env.PORT || 5e3);
  const host = process.env.HOST || "127.0.0.1";
  const listenOpts = { port, host };
  if (process.platform !== "win32") {
    listenOpts.reusePort = true;
  }
  server.listen(listenOpts, () => {
    log(`serving on http://${host}:${port}`);
  });
})();
