import { Component } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive, Router } from '@angular/router';
import { AuthService } from './services/auth.service';
import { ApiService } from './services/api';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, CommonModule],
  templateUrl: './app.html',
})
export class AppComponent {
  menuAberto = false;

  constructor(
    public authService: AuthService,
    private api: ApiService,
    private router: Router,
  ) {}

  sair() {
    this.menuAberto = false;
    this.api.logout().subscribe({
      next: () => this.encerrar(),
      error: () => this.encerrar(),
    });
  }

  private encerrar() {
    this.authService.logout();
    this.router.navigate(['/']);
  }
}
