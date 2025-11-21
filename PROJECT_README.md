# 🚀 GEMVC Framework Project

This project is based on the **GEMVC Framework** - a multi-platform PHP REST API framework.

## ⚠️ Important: GEMVC is NOT Laravel or Symfony

**GEMVC has its own unique architecture, naming conventions, and design philosophy.** It is NOT Laravel or Symfony. Please do not use Laravel or Symfony patterns when working with this project.

---

## 🤖 For AI Assistants - Required Reading

If you are an AI assistant (Cursor AI, GitHub Copilot, Claude, etc.), you **MUST read** the following documentation files in the order specified below. All files are located in `vendor/gemvc/library/`.

### 📖 Reading Order (MANDATORY)

#### 1. Master Instructions (Read First)
- **`vendor/gemvc/library/QUICK_START_AI.md`** - **MASTER INSTRUCTIONS** for AI assistants. This file instructs you to read ALL GEMVC documentation files, teaches the complete 4-layer architecture (MANDATORY), shows correct method signatures, explains security features (90% automatic), provides code generation patterns you MUST follow, and lists common mistakes you MUST avoid.

#### 2. Core Documentation (Read Second)
- **`vendor/gemvc/library/README.md`** - Main framework overview, architecture explanation, 4-layer pattern details, examples, and key differences from Laravel/Symfony. Contains the "FOR AI ASSISTANTS - READ THIS FIRST!" section.

- **`vendor/gemvc/library/GEMVC_GUIDE.md`** - Quick reference guide for code generation. Contains concise patterns, CLI commands reference, and essential code examples for GitHub Copilot and similar AI assistants.

- **`vendor/gemvc/library/AI_CONTEXT.md`** - Quick reference guide with framework overview, key patterns, common tasks, and fast lookup examples. Essential for understanding GEMVC's philosophy and architecture.

- **`vendor/gemvc/library/COPILOT_INSTRUCTIONS.md`** - Simple instructions specifically for GitHub Copilot. Contains quick reference, essential patterns, and common mistakes to avoid.

#### 3. API Reference (Read Third)
- **`vendor/gemvc/library/AI_API_REFERENCE.md`** - Complete API documentation with all method signatures, parameters, return types, and code patterns. Essential for understanding Request, Response, Table ORM, and all framework classes.

- **`vendor/gemvc/library/GEMVC_PHPDOC_REFERENCE.php`** - PHPDoc annotations for type hints. Provides proper type hints, method signatures, and Copilot-friendly documentation for code completion.

- **`vendor/gemvc/library/gemvc-api-reference.jsonc`** - Structured framework data in JSONC format for programmatic access. Contains machine-readable framework metadata, API structure, validation types, and common patterns.

#### 4. Architecture & Deep Dive (Read Fourth)
- **`vendor/gemvc/library/ARCHITECTURE.md`** - Complete architecture overview. Explains directory structure, component breakdown, design patterns used, request flow for Apache and OpenSwoole, and performance optimizations.

- **`vendor/gemvc/library/HTTP_REQUEST_LIFE_CYCLE.md`** - Server-agnostic HTTP request handling documentation. Explains how server adapters (ApacheRequest, SwooleRequest) work, unified Request object design, request life cycle for Apache and OpenSwoole, and automatic input sanitization.

- **`vendor/gemvc/library/DATABASE_LAYER.md`** - Complete guide to the Table layer. Explains how all table classes must extend Table, understanding `$_type_map` property, using `defineSchema()` for constraints, property mapping and visibility rules, and schema constraints (primary, unique, foreign keys, indexes).

#### 5. Security & Templates (Read Fifth)
- **`vendor/gemvc/library/SECURITY.md`** - Comprehensive security overview. Details multi-layer security architecture, 90% automatic security coverage, input sanitization (XSS prevention), SQL injection prevention, header sanitization, file security, JWT authentication/authorization, and attack prevention mechanisms.

- **`vendor/gemvc/library/TEMPLATE_SYSTEM.md`** - Customizable template system documentation. Explains how templates are copied during `gemvc init`, template lookup priority (project vs vendor), available templates (service, controller, model, table), template variables and replacement, and customization examples.

#### 6. CLI & Advanced Topics (Read Sixth)
- **`vendor/gemvc/library/CLI.md`** - Complete CLI commands reference. Documents all command-line tools, code generation, project management, command architecture and class hierarchy, how commands extend Command base class, and step-by-step examples.

- **`vendor/gemvc/library/GEMVC_DOCUMENTATION_DIRECTIVES.md`** - Auto-documentation system guide. Explains PHPDoc directives reference, how to create beautiful API documentation automatically, and the documentation generator system.

- **`vendor/gemvc/library/GEMVC.md`** - Comprehensive framework assessment and comparison. Provides executive summary, microservice architecture design, feature comparison with Laravel/Symfony, and when to use GEMVC.

#### 7. Setup Guide (Reference)
- **`vendor/gemvc/library/AI_ASSISTANT_SETUP.md`** - Overview of all documentation files. Explains how different AI assistants (Cursor, Copilot, etc.) use these files, file organization, and best practices for AI assistant integration.

---

## 🎯 Critical Framework Rules

Before reading the documentation, understand these critical points:

1. **4-Layer Architecture is MANDATORY** - Never skip layers:
   - API Layer (app/api/) → Schema validation, authentication
   - Controller Layer (app/controller/) → Business logic orchestration
   - Model Layer (app/model/) → Data logic, validations, transformations
   - Table Layer (app/table/) → Database operations

2. **Security is 90% Automatic** - No manual sanitization needed:
   - Input sanitization is AUTOMATIC ✅
   - SQL injection prevention is AUTOMATIC ✅
   - Header sanitization is AUTOMATIC ✅
   - Path protection is AUTOMATIC ✅
   - You ONLY call: `definePostSchema()` and `auth()`

3. **GEMVC is NOT Laravel or Symfony** - Do not use Laravel/Symfony patterns:
   - No routes config files (URL mapping is automatic)
   - No Eloquent-style ORM syntax
   - No manual sanitization (already automatic)
   - Different architecture and conventions

4. **Type Safety is Critical** - PHPStan Level 9 compliance required

5. **Properties Match Database Columns** - Exact names required

6. **Use `_` Prefix for Aggregation** - Properties starting with `_` are ignored in CRUD operations

7. **Use `protected` for Sensitive Data** - Hidden from SELECT queries

---

## 📚 Quick Start

After reading the documentation above, you can:

1. Generate CRUD operations: `gemvc create:crud ServiceName`
2. Migrate database tables: `gemvc db:migrate TableClassName`
3. View API documentation: Visit `/api/index/document`

---

**Remember**: Read all documentation files in the specified order to fully understand GEMVC framework architecture, patterns, and conventions.

